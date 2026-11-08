# Deploy and use
Deploy render.yaml as a Render Blueprint. Set the independent model base URL, key, model ID and a 32+ character random gateway key. Set BRAVE_SEARCH_KEY to enable the web-research option. Requests use Brave LLM Context passages; the model drafts a claim, the server checks an exact supporting quote and requests a second evidence review. A positive review cannot guarantee truth. The original domains are displayed as URLs. Resource limits and a live model call must be tested before inviting students.

## Connect the v4 website
Back up the cPanel site. Merge website/private and website/public_html into the corresponding v4 directories (index.php only adds the AI Voice Tutor navigation link). Apply the existing v4 document-library migrations and website/private/shared_books.sql. Keep Gemini enabled. In private/config.php set hermes_backup_url to the HTTPS Render origin and hermes_backup_key to the matching key. Ordinary tutoring tries Gemini, then Hermes for temporary failures. Web research uses Hermes directly. Book-matching passages and recent turns accompany ordinary tutoring. The voice page uses an authenticated website session, HTTPS and a browser supporting speech recognition. Start voice chat, allow microphone access, or use text. Stop ends listening/playback. Speech recognition and available system voices vary by browser. This is turn-based conversation.

## Keep books available after restarts
Private student uploads use the existing My Library page and the site's MySQL storage. For image/scanned-PDF ingestion configure pdf_text_binary, pdf_render_binary, ocr_binary and ocr_languages (ben+eng) on the website host. Its upload and page limits still apply. A student's materials reach external models only with the student's existing opt-in. Keep copyrighted books, private videos and accounts' credentials out of this public repository.

For shared course books, an authorized operator indexes the whole book locally and imports the result into the site's private MySQL database. Install pypdfium2, Pillow, and Tesseract with the English and Bengali language packs. For PDFs each page is extracted or OCR'd as needed; image files and UTF-8 text are supported. Review OCR output before importing. Each record keeps the original PDF page number. Example:

    python admin/index_book.py lesson.pdf --title "Course book" --rights-confirmed > course.jsonl
    php /path/to/private/import_book.php < course.jsonl

Transfer the JSONL privately to the web host for the second command. Re-importing identical bytes refreshes the existing passages in a transaction. Removing a book from hermes_books deletes its passages. The model retrieves these passages at question time. Uploaded books do not update model weights.

## Teacher appearance, instruction and speech
Use only submitted materials authorized for AI use by everyone recorded. On an operator machine install ffmpeg, faster-whisper and openai. Configure STYLE_MODEL_BASE_URL, STYLE_MODEL_KEY and a vision-capable STYLE_MODEL. The tool transcribes locally and sends three sampled frames and transcript excerpts to the configured model:

    python teacher/prepare.py class.mp4 --teacher-consent --output draft.txt

Have the teacher review the draft. Install the approved file at private/teacher_style.txt and set teacher_profile_approved=true. An approved portrait may be placed at public_html/teacher-avatar.jpg. The AI label stays visible. To use a particular voice, the teacher must enroll and authorize that voice in the speech provider. Configure teacher_voice_approved, teacher_voice_id and elevenlabs_api_key privately. Each student can opt in to hear their tutor answer using that voice. Clearing the approval setting disables access. The app does not create a voiceprint from a portrait or classroom footage. No real teacher media, voice ID or keys ship with this code.

## Import a teacher's public YouTube classes
Apply website/private/youtube_classes.sql to the website database. In private/config.php set class_teacher_ids to an array of authorized teacher user IDs and class_teacher_names to an ID-to-display-name map. Use a separate 32+ character random class_import_key, and set the matching CLASS_IMPORT_KEY on Render. Set CLASS_VIDEO_API_KEY and CLASS_VIDEO_MODEL on Render to a Gemini credential and model with public YouTube video understanding. Video analysis is an optional provider-dependent import step. Supply secrets privately.

The teacher opens AI Voice Tutor → আমার YouTube ক্লাস যোগ করুন, submits a public watch/share/live/shorts link and confirms permission. The model proposes timestamped lesson and delivery observations from audio and video; there is no need to export captions. Check the moments in the source, select accurate observations, then approve. Reanalyzing creates a new draft without replacing the approved notes until reviewed. Remove a class to withdraw its stored notes. A student selects the teacher on the voice-chat page. Ordinary tutoring applies that teacher's approved methods and retrieves matching lesson notes, with source timestamps. Private videos use the existing local teacher/prepare.py workflow. Source notes do not affect model weights or enroll a voice.

## Manage teacher access
Apply website/private/teacher_access.sql before enabling the admin panel. Register a separate ordinary account for the administrator, verify its numeric users.id on the host, and put that ID in the admin_user_ids array in private/config.php. Sign in with that account, open AI Voice Tutor → অ্যাডমিন সেকশন (or public path /admin/), and find registered teachers by email to grant or revoke access. Existing configuration-based teachers stay active until overridden. Revocation also removes their classes from the tutor selector. Keep the operator's student account separate if desired.
