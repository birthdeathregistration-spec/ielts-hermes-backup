'use strict';
(() => {
  const el = id => document.getElementById(id);
  const s={resources:[],videos:[],speaking:[],speakIdx:-1,speakUntil:0,speakTick:null,mocks:[],mock:null,mockSlug:'',mockUntil:0,mockTick:null,rec:null,stream:null,blobUrl:null,recognition:null};
  const note=(message)=>showToast(message);
  const node=(tag,content,cls='')=>{const x=document.createElement(tag);x.textContent=content;if(cls)x.className=cls;return x;};
  const clean=(target)=>target.replaceChildren();
  const clock=s=>`${String(Math.floor(Math.max(0,s)/60)).padStart(2,'0')}:${String(Math.max(0,s)%60).padStart(2,'0')}`;
  const speakText=(text)=>{
    if (!('speechSynthesis' in window))return note('This browser has no speech synthesis. Read the question on screen.');
    window.speechSynthesis.cancel(); const u=new SpeechSynthesisUtterance(text);u.lang='en-GB';u.rate=.88;window.speechSynthesis.speak(u);
  };
  function resources(){
    const skill=el('resourceSkill').value;
    const container=el('resourceList');clean(container);
    s.resources.filter(r=>skill==='all'||r.skill===skill||r.skill==='all').forEach(r=>{
      const card=node('article','','resource-card');
      const heading=node('strong',r.name);const desc=node('p',`${r.publisher} · ${r.skill==='all'?'All skills':r.skill}`,'muted');
      const link=node('a',r.url);link.style.overflowWrap='anywhere';link.href=r.url;link.target='_blank';link.rel='noopener noreferrer';
      card.append(heading,desc,link);container.append(card);
    });
  }
  function showVideo(video){
    const player=el('videoPlayer');
    player.src='https://www.youtube-nocookie.com/embed/'+video.video_id;
    el('videoWatch').href='https://www.youtube.com/watch?v='+video.video_id;
    el('videoPlayerWrap').classList.remove('hidden');
    window.activeVideoNoteId=Number(video.id);
  }
  function renderVideos(){
    const parent=el('videoList');clean(parent);
    if(!s.videos.length){parent.append(node('p','No classes saved yet.','muted'));return;}
    s.videos.forEach(v=>{
      const row=node('div','','document-item');const info=node('div');
      info.append(node('strong',v.title),node('small',v.notes_text?`My notes: ${v.notes_text.length} chars · AI consent: ${Number(v.external_ai_consent)?'yes':'no'}`:'No notes yet'));
      const buttons=node('div','','control-row');
      const watch=node('button','Open video','secondary');watch.type='button';watch.addEventListener('click',()=>showVideo(v));
      const ask=node('button','Ask about my notes','secondary');ask.type='button';ask.addEventListener('click',()=>{
        window.activeVideoNoteId=Number(v.id);
        el('coachQuestion').value=`আমার '${v.title}' ক্লাসের নোট অনুযায়ী মূল বিষয়গুলো বাংলায় ব্যাখ্যা করো এবং তিনটি practice question দাও।`;
        changeTab('coach');el('coachQuestion').focus();
      });
      const remove=node('button','Delete','text-btn');remove.type='button';remove.addEventListener('click',async()=>{
        if(!confirm('Delete your saved class and notes?'))return;
        try{await api('video_notes.php','POST',{action:'delete',id:Number(v.id)});if(window.activeVideoNoteId===Number(v.id))window.activeVideoNoteId=null;await loadVideos();}catch(e){note(e.message);}
      });
      buttons.append(watch,ask,remove);row.append(info,buttons);parent.append(row);
    });
  }
  async function loadVideos(){const r=await api('video_notes.php');s.videos=r.videos||[];renderVideos();}
  async function saveVideo(e){e.preventDefault();const button=el('videoSave');button.disabled=true;
    try{await api('video_notes.php','POST',{
      url:el('videoUrl').value.trim(),title:el('videoTitle').value.trim(),notes:el('videoNotes').value.trim(),ai_consent:el('videoAiConsent').checked
    });el('videoForm').reset();await loadVideos();note('Class link and your notes saved privately.');}
    catch(error){note(error.message);}finally{button.disabled=false;}
  }
  function stopSpeakClock(){clearInterval(s.speakTick);s.speakTick=null;}
  function promptSpeaking(){
    const p=s.speaking[s.speakIdx];if(!p)return;
    stopSpeakClock();el('speakStage').textContent=p.part.toUpperCase()+' · Question '+(s.speakIdx+1)+'/'+s.speaking.length;
    el('speakPrompt').textContent=p.text;el('speakTranscript').value='';el('speakFeedback').classList.add('hidden');
    if(p.part==='part2') {
      el('speakStage').textContent='PART 2 · One-minute preparation (then speak up to 2 minutes)';
      s.speakUntil=Date.now()+60_000;
      el('speakClock').textContent='Prepare 01:00';
      s.speakTick=setInterval(()=>{
        const n=Math.max(0,Math.ceil((s.speakUntil-Date.now())/1000));
        if(n<=0){stopSpeakClock();el('speakStage').textContent='PART 2 · Speak for up to 2 minutes';speakText(p.text);beginSpeakingClock(120);}
        else el('speakClock').textContent='Prepare '+clock(n);
      },250);
    } else {el('speakClock').textContent='—';speakText(p.text);}
  }
  function beginSpeakingClock(seconds){
    stopSpeakClock();s.speakUntil=Date.now()+seconds*1000;
    s.speakTick=setInterval(()=>{
      const n=Math.max(0,Math.ceil((s.speakUntil-Date.now())/1000));el('speakClock').textContent=clock(n);
      if(n<=0){stopSpeakClock();note('Suggested time finished. Save your answer or continue.');}
    },300);
  }
  function nextSpeaking(){
    if(!s.speaking.length)return note('Speaking prompts could not load.');
    if(s.speakIdx>=s.speaking.length-1){stopSpeakClock();note('Practice finished. Restart to practise again.');return;}
    s.speakIdx++;promptSpeaking();
  }
  function dictate(){
    const SpeechRecognition=window.SpeechRecognition||window.webkitSpeechRecognition;
    if(!SpeechRecognition){note('Speech recognition is unavailable in this browser. You can type your answer.');return;}
    if(s.recognition){s.recognition.stop();return;}
    const rec=new SpeechRecognition();rec.lang='en-GB';rec.continuous=true;rec.interimResults=false;
    s.recognition=rec;el('speakMic').textContent='Stop dictation';
    rec.onresult=(e)=>{
      let words='';for(let i=e.resultIndex;i<e.results.length;i++)if(e.results[i].isFinal)words+=e.results[i][0].transcript+' ';
      if(words)el('speakTranscript').value+=(el('speakTranscript').value.trim()?' ':'')+words.trim();
    };
    rec.onerror=(e)=>note('Speech recognition: '+e.error+' (try typing your answer)');
    rec.onend=()=>{s.recognition=null;el('speakMic').textContent='🎙 Dictate answer';};
    try{rec.start();}catch(e){s.recognition=null;note(e.message);}
  }
  async function loadSpeaking(){
    const r=await api('speaking.php');s.speaking=r.prompts||[];const history=el('speakingHistory');clean(history);
    (r.history||[]).forEach(h=>history.append(node('p',`${h.part.toUpperCase()} · ${h.word_count} words · ${h.created_at}`,'muted')));
    if(!(r.history||[]).length)history.append(node('p','No attempts saved yet.','muted'));
  }
  async function saveSpeaking(){const p=s.speaking[s.speakIdx];if(!p)return note('Start speaking practice first.');
    const text=el('speakTranscript').value.trim();if(text.length<3)return note('Speak or type your answer before saving.');
    try{
      const r=await api('speaking.php','POST',{part:p.part,prompt_key:p.key,transcript:text});
      el('speakFeedback').textContent=r.feedback.summary_bn+'\n\n'+r.feedback.tips.map((x,i)=>(i+1)+'. '+x).join('\n');el('speakFeedback').classList.remove('hidden');await loadSpeaking();
    }catch(e){note(e.message);}
  }
  async function startRecording(){
    if(!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder)return note('Recording is not available in this browser.');
    try{
      s.stream=await navigator.mediaDevices.getUserMedia({audio:true});
      const chunks=[];
      s.rec=new MediaRecorder(s.stream);s.rec.ondataavailable=e=>{if(e.data.size)chunks.push(e.data);};
      s.rec.onstop=()=>{
        if(s.blobUrl)URL.revokeObjectURL(s.blobUrl);
        s.blobUrl=URL.createObjectURL(new Blob(chunks,{type:s.rec.mimeType||'audio/webm'}));
        el('voicePreview').src=s.blobUrl;el('voicePreview').classList.remove('hidden');
        s.stream?.getTracks().forEach(t=>t.stop());s.stream=null;
      };
      s.rec.start();el('recordStart').disabled=true;el('recordStop').disabled=false;
      // Limit recording to 3 minutes to reduce resource usage.
      setTimeout(()=>{if(s.rec?.state==='recording')stopRecording();},180000);
    }catch(e){note('Cannot access microphone: '+e.message);}
  }
  function stopRecording(){if(s.rec?.state==='recording')s.rec.stop();el('recordStart').disabled=false;el('recordStop').disabled=true;}
  function renderMocks(){const select=el('mockSelect');clean(select);s.mocks.forEach(m=>{const x=node('option',`${m.title} (${m.questions} Q · ${m.minutes} min)`);x.value=m.slug;select.append(x);});}
  async function loadMocks(){const r=await api('mini_mocks.php');s.mocks=r.mocks||[];renderMocks();
    const box=el('mockHistory');clean(box);(r.history||[]).forEach(h=>box.append(node('p',`${h.test_slug}: ${h.correct_count}/${h.total_count} · ${h.created_at}`,'muted')));
    if(!(r.history||[]).length)box.append(node('p','No mini mocks submitted yet.','muted'));
  }
  function stopMockClock(){clearInterval(s.mockTick);s.mockTick=null;}
  async function startMock(){
    try{
      const slug=el('mockSelect').value;if(!slug)return note('Choose a mini mock.');
      const r=await api('mini_mocks.php?slug='+encodeURIComponent(slug));s.mock=r.mock;s.mockSlug=slug;
      el('mockTitle').textContent=s.mock.title;el('mockPassage').textContent=s.mock.passage||'';
      el('mockAudio').classList.toggle('hidden',s.mock.skill!=='listening');
      el('mockExam').classList.remove('hidden');el('mockResults').classList.add('hidden');
      const qbox=el('mockQuestions');clean(qbox);
      s.mock.questions.forEach((q,i)=>{
        const fs=document.createElement('fieldset');fs.className='mock-question';fs.append(node('legend',`${i+1}. ${q.text}`));
        q.options.forEach(opt=>{
          const label=node('label','','choice');const input=document.createElement('input');input.type='radio';input.name=q.id;input.value=opt;
          label.append(input,node('span',opt));fs.append(label);
        });qbox.append(fs);
      });
      stopMockClock();s.mockUntil=Date.now()+s.mock.minutes*60000;
      s.mockTick=setInterval(()=>{const n=Math.max(0,Math.ceil((s.mockUntil-Date.now())/1000));el('mockClock').textContent=clock(n);
        if(!n){stopMockClock();note('Time is up. Submit your answers.');}
      },300);
      el('mockExam').scrollIntoView({behavior:'smooth',block:'start'});
    }catch(e){note(e.message);}
  }
  async function submitMock(e){e.preventDefault();if(!s.mock)return;
    const answers={};s.mock.questions.forEach(q=>{const picked=document.querySelector(`#mockQuestions input[name="${q.id}"]:checked`);answers[q.id]=picked?.value||'';});
    const button=el('mockSubmit');button.disabled=true;
    try{
      const r=await api('mini_mocks.php','POST',{slug:s.mockSlug,answers});stopMockClock();window.speechSynthesis?.cancel();
      const pane=el('mockResults');clean(pane);pane.append(node('h3',`Result: ${r.result.correct}/${r.result.total} correct`),node('p',r.result.disclaimer,'muted'));
      r.result.details.forEach(x=>pane.append(node('p',`${x.is_correct?'✓':'✗'} ${x.question}\nYour answer: ${x.selected||'(blank)'}\nCorrect answer: ${x.correct_answer}\nWhy: ${x.reason}`,'mock-review')));
      pane.classList.remove('hidden');await loadMocks();
    }catch(err){note(err.message);}finally{button.disabled=false;}
  }
  async function refresh(){
    const tasks=[api('resources.php').then(r=>{s.resources=r.resources||[];resources();}),loadVideos(),loadSpeaking(),loadMocks()];
    const results=await Promise.allSettled(tasks);
    const failed=results.find(x=>x.status==='rejected');if(failed)note('Some practice modules could not load: '+failed.reason.message);
  }
  function reset(){
    stopSpeakClock();stopMockClock();window.speechSynthesis?.cancel();s.recognition?.stop();stopRecording();
    if(s.blobUrl)URL.revokeObjectURL(s.blobUrl);s.blobUrl=null;
    el('voicePreview').removeAttribute('src');el('voicePreview').classList.add('hidden');
    el('videoPlayer').removeAttribute('src');el('videoPlayerWrap').classList.add('hidden');
    window.activeVideoNoteId=null;s.speakIdx=-1;s.mock=null;s.videos=[];s.speaking=[];
  }
  el('resourceSkill').addEventListener('change',resources);
  el('videoForm').addEventListener('submit',saveVideo);
  el('speakStart').addEventListener('click',()=>{s.speakIdx=-1;nextSpeaking();});
  el('speakNext').addEventListener('click',nextSpeaking);
  el('speakRead').addEventListener('click',()=>{const p=s.speaking[s.speakIdx];if(p)speakText(p.text);});
  el('speakMic').addEventListener('click',dictate);
  el('speakSubmit').addEventListener('click',saveSpeaking);
  el('recordStart').addEventListener('click',startRecording);
  el('recordStop').addEventListener('click',stopRecording);
  el('mockStart').addEventListener('click',startMock);
  el('mockForm').addEventListener('submit',submitMock);
  el('mockListen').addEventListener('click',()=>{if(s.mock?.skill==='listening')speakText(s.mock.speech_script);});
  el('mockStopAudio').addEventListener('click',()=>window.speechSynthesis?.cancel());
  window.learningFeature={refresh,reset};
})();
