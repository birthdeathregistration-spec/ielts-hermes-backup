<?php
declare(strict_types=1);

/**
 * Secure document ingest: no original binaries saved and no model training.
 * Max indexed text 70k Unicode chars; max first 30 pages of PDF, first 3 pages OCR fallback.
 * These are MVP limits, NOT a promise to fully ingest every book.
 */
function document_type(string $path): ?string {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $header = (string)file_get_contents($path, false, null, 0, 10);
    if ($mime === 'application/pdf' && str_starts_with($header, '%PDF-')) return 'pdf';
    if ($mime === 'image/jpeg' && substr($header, 0, 3) === "\xFF\xD8\xFF") return 'jpeg';
    if ($mime === 'image/png' && str_starts_with($header, "\x89PNG\r\n\x1a\n")) return 'png';
    if (in_array($mime,['text/plain','text/x-php'],true) && $mime==='text/plain') return 'txt';
    return null;
}

function normalize_document_text(string $text): string {
    $text = str_replace(["\r\n","\r"],"\n",$text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',' ',$text) ?? '';
    $text = preg_replace('/[ \t]+/u',' ',$text) ?? '';
    return trim(preg_replace('/\n{4,}/u',"\n\n\n",$text) ?? '');
}

function split_document_text(string $text, int $limit=70_000): array {
    $text = normalize_document_text($text);
    $wasTruncated = mb_strlen($text)>$limit;
    $text = mb_substr($text,0,$limit);
    $chunks = []; $position=0; $length=mb_strlen($text);
    while ($position<$length && count($chunks)<70) {
        $part=trim(mb_substr($text,$position,1200));
        if (mb_strlen($part)>=25) $chunks[]=$part;
        $position+=1050;
    }
    return ['chunks'=>$chunks,'chars'=>$length,'truncated'=>$wasTruncated];
}

/** Execute a trusted, configured binary with argv array (no shell). Return null on missing binary/error. */
function document_command(array $args, int $maxOutput=900_000): ?string {
    if (!function_exists('proc_open')) return null;
    $exe=$args[0]??'';
    if (!is_string($exe) || !str_starts_with($exe,'/') || !is_executable($exe)) return null;
    try {
        $pipes=[];
        $process=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        if (!is_resource($process)) return null;
        fclose($pipes[0]);
        $output=stream_get_contents($pipes[1],$maxOutput+1);
        fclose($pipes[1]);
        // Avoid outputting command errors to clients/logging untrusted file text.
        stream_get_contents($pipes[2],65536); fclose($pipes[2]);
        $status=proc_close($process);
        if ($status!==0 || $output===false || strlen($output)>$maxOutput) return null;
        return $output;
    } catch (Throwable $e) { return null; }
}

function extract_document_text(string $path,string $kind,array $settings): array {
    $info=''; $text='';
    if ($kind==='txt') {
        $text=(string)file_get_contents($path);
        if (!mb_check_encoding($text,'UTF-8')) throw new RuntimeException('TXT file must be UTF-8.');
        $info='UTF-8 text extracted.';
    } elseif ($kind==='pdf') {
        $binary=(string)($settings['pdf_text_binary']??'');
        if ($binary!=='') {
            $out=document_command([$binary,'-f','1','-l','30','-enc','UTF-8',$path,'-']);
            if ($out!==null) $text=$out;
        }
        if (trim($text)==='' && is_file(__DIR__.'/vendor/autoload.php')) {
            require_once __DIR__.'/vendor/autoload.php';
            if (class_exists('Smalot\\PdfParser\\Parser')) {
                try {
                    $cfg=new \Smalot\PdfParser\Config();
                    $cfg->setDecodeMemoryLimit(20*1024*1024);
                    $cfg->setRetainImageContent(false);
                    $parser=new \Smalot\PdfParser\Parser([], $cfg);
                    $pdf=$parser->parseFile($path);
                    foreach (array_slice($pdf->getPages(),0,30) as $page) {
                        $text.=$page->getText()."\n\n";
                        if (strlen($text)>600_000) break;
                    }
                } catch (Throwable $e) { $text=''; }
            }
        }
        if (mb_strlen(trim($text))<40) {
            $ocr=(string)($settings['ocr_binary']??'');
            $renderer=(string)($settings['pdf_render_binary']??'');
            if ($ocr!=='' && $renderer!=='' && is_executable($ocr) && is_executable($renderer) && function_exists('proc_open')) {
                $tempDir=sys_get_temp_dir().'/ielts_ocr_'.bin2hex(random_bytes(8));
                if (!mkdir($tempDir,0700)) throw new RuntimeException('Temporary OCR directory unavailable.');
                try {
                    $prefix=$tempDir.'/page';
                    document_command([$renderer,'-f','1','-l','3','-scale-to','1600','-png','-r','110',$path,$prefix],512);
                    $pages=glob($tempDir.'/page-*.png')?:[];
                    sort($pages,SORT_NATURAL);
                    foreach (array_slice($pages,0,3) as $image) {
                        $recognized=document_command([$ocr,$image,'stdout','-l',(string)($settings['ocr_languages']??'eng')]);
                        if ($recognized!==null) $text.=$recognized."\n\n";
                    }
                } finally {
                    foreach (glob($tempDir.'/*')?:[] as $file) @unlink($file);
                    @rmdir($tempDir);
                }
                $info='Scanned PDF OCR covers only first 3 pages in the MVP.';
            }
        } else $info='Text PDF: at most first 30 pages indexed.';
    } elseif (in_array($kind,['jpeg','png'],true)) {
        $dimensions=@getimagesize($path);
        if (!$dimensions || $dimensions[0]<10 || $dimensions[1]<10 || $dimensions[0]*$dimensions[1]>16_000_000) {
            throw new RuntimeException('Image dimensions are invalid or too large (max 16 megapixels).');
        }
        $ocr=(string)($settings['ocr_binary']??'');
        if ($ocr!=='') {
            $out=document_command([$ocr,$path,'stdout','-l',(string)($settings['ocr_languages']??'eng')]);
            if ($out!==null) $text=$out;
        }
        $info='Image OCR requires Tesseract installed and configured on hosting.';
    }
    $result=split_document_text($text);
    $result['status']=$result['chars']>=40 && count($result['chunks'])>0 ? 'indexed' : 'needs_text';
    $result['note']=$result['status']==='indexed' ? $info : 'Automatic text extraction unavailable or found too little text. Paste extracted text, or configure OCR/PDF extraction.';
    return $result;
}
