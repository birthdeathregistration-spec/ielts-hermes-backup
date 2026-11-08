const el = id => document.getElementById(id);
const headers = {'Content-Type':'application/json','X-CSRF-Token':document.querySelector('[name=csrf-token]').content};
const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let live=false, generation=0, listener=null, audio=null, busy=false;
const status = text => { el('status').textContent=text; };
function stop() {
  live=false; generation++;
  if (listener) listener.abort();
  if (audio) { audio.pause(); audio=null; }
  window.speechSynthesis?.cancel();
  status('ভয়েস চ্যাট বন্ধ।');
}
function listen() {
  if (!live || busy) return;
  const id=generation, rec=new Recognition();
  listener=rec; rec.lang=el('language').value; rec.interimResults=false;
  rec.onresult=e=>{ if(id===generation){ el('question').value=e.results[0][0].transcript; ask(); } };
  rec.onerror=e=>{ if(id===generation){stop();status('মাইক্রোফোন: '+e.error+'। আবার শুরু করো অথবা লিখে পাঠাও।');} };
  rec.onend=()=>{if(id===generation && !busy && live && listener===rec){stop();status('আবার বলতে ভয়েস চ্যাট শুরু করো।');}};
  try {rec.start();status('শুনছি…');} catch {stop();}
}
async function speak(text,id) {
  if(el('teacher').checked) {
    const r=await fetch('api/teacher_voice.php',{method:'POST',headers,body:'{}'});
    if(r.ok && id===generation){
      const url=URL.createObjectURL(await r.blob());
      if(id!==generation){URL.revokeObjectURL(url);return;}
      const player=new Audio(url); audio=player;
      const done=()=>{URL.revokeObjectURL(url); if(id===generation) listen();};
      player.onended=done;player.onerror=done;player.onpause=()=>{if(id!==generation)URL.revokeObjectURL(url);};
      try {await player.play();return;} catch {URL.revokeObjectURL(url);}
    }
  }
  if(id!==generation)return;
  if(!window.speechSynthesis){stop();return;}
  const utterance=new SpeechSynthesisUtterance(text.replace(/https?:\/\/\S+/g,''));
  utterance.lang=el('language').value;utterance.rate=0.94;
  const voices=speechSynthesis.getVoices(),lang=utterance.lang.toLowerCase();
  utterance.voice=voices.find(v=>v.lang.toLowerCase()===lang)||voices.find(v=>v.lang.toLowerCase().startsWith(lang.slice(0,2)))||null;
  utterance.onend=()=>{if(id===generation)listen();};
  utterance.onerror=()=>{if(id===generation){stop();status('অডিও চালানো যায়নি। উত্তরটি পড়তে পারো।');}};
  speechSynthesis.speak(utterance);
}
async function ask() {
  const question=el('question').value.trim(); if(!question||busy)return;
  const id=generation;busy=true;el('send').disabled=true;
  if(listener){listener.onend=null; listener.onerror=null;listener.stop();listener=null;}
  const append=(who,text)=>{const p=document.createElement('p');p.textContent=who+' · '+text;el('chat').appendChild(p);};
  append('তুমি',question);el('question').value='';status('উত্তর তৈরি হচ্ছে…');
  try {
    const r=await fetch('api/hermes_tutor.php',{method:'POST',headers,body:JSON.stringify({question,language:el('language').value.slice(0,2),research:el('research').checked})});
    const data=await r.json();if(!r.ok)throw Error(data.error||'উত্তর পাওয়া যায়নি');
    append('AI',data.answer);status('');
    if(live && id===generation){status('AI বলছে…');await speak(data.answer,id);}
  }catch(e){stop();status(e.message);}finally{busy=false;el('send').disabled=false;}
}
el('form').onsubmit=e=>{e.preventDefault();ask();};
el('start').onclick=()=>{if(busy||live)return;live=true;generation++;listen();};
el('stop').onclick=stop;
if(!Recognition){el('start').disabled=true;status('এই ব্রাউজারে লিখে প্রশ্ন করা যাবে।');}
