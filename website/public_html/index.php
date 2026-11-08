<?php
declare(strict_types=1);
require_once __DIR__ . '/../private/bootstrap.php';
?>
<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="#111c3d">
  <title>IELTS AI Teacher — Practice Lab</title>
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/app.js" defer></script>
  <script src="assets/library.js" defer></script>
  <script src="assets/learning.js" defer></script>
</head>
<body>
  <header class="site-header">
    <div class="brand"><span class="brand-logo">A<span class="spark">✦</span></span><div><b>IELTS AI Teacher</b><small>Free community learning · বাংলা + English</small></div></div>
    <div id="userControls" class="user-controls hidden"><span id="greeting"></span><button id="logoutBtn" class="text-btn">Log out</button></div>
  </header>
  <main class="shell">
    <section id="authScreen" class="auth-panel card">
      <div class="eyebrow">FREE FOR HEXA’S ZINDABAZAR & ALL STUDENTS</div>
      <h1>তোমার Personal IELTS AI Teacher</h1>
      <p class="muted">Hexa’s Zindabazar-এর শিক্ষার্থীসহ সবার জন্য সম্পূর্ণ ফ্রি IELTS practice। নিজস্ব লেসন, Writing Task 2 এবং বাংলা Study Guide—কোনো ফি নেই।</p>
      <div class="mode-toggle"><button type="button" class="active" id="showLogin">Log in</button><button type="button" id="showRegister">Create account</button></div>
      <form id="authForm">
        <label id="nameRow" class="hidden">Display name (real name is optional)<input id="displayName" autocomplete="nickname" maxlength="80" placeholder="e.g. Learner" /></label>
        <label id="communityRow" class="hidden">Student group (optional)<select id="community"><option value="prefer_not_to_say">Prefer not to say</option><option value="hexa_zindabazar">Hexa’s Zindabazar</option><option value="other">Other / independent learner</option></select></label>
        <label>Email address<input id="email" type="email" autocomplete="email" required placeholder="you@example.com" /></label>
        <label>Password<input id="password" type="password" autocomplete="current-password" required minlength="10" placeholder="At least 10 characters" /></label>
        <button class="primary full" id="authSubmit" type="submit">Log in</button>
      </form>
      <p class="fineprint">এই সেবা সবার জন্য বিনামূল্যে এবং কোনো সরকারি/অফিশিয়াল IELTS সেবা নয়। AI provider ডিফল্টভাবে বন্ধ; সে অবস্থায় কেবল local study guide ও structure review দেওয়া হয়। <a href="privacy.html">Privacy & data policy</a> পড়ো।</p>
    </section>

    <div id="appScreen" class="hidden">
      <section class="hero">
        <div><div class="eyebrow light">YOUR PERSONAL LEARNING SPACE</div><h1>Practice. Improve. Repeat.</h1><p>একটি বিষয়ে শিখো, লেখো, AI থেকে feedback নাও, তারপর অগ্রগতি দেখো।</p></div>
        <div class="hero-number"><strong>৳0</strong><span>Free for every student</span></div>
      </section>
      <nav class="tabs" aria-label="Learning sections">
        <a class="tab" href="hermes.php">AI Voice Tutor</a>
        <button type="button" data-tab="dashboard" class="tab active">Overview</button>
        <button type="button" data-tab="lessons" class="tab">Study Materials</button>
        <button type="button" data-tab="writing" class="tab">Writing Room</button>
        <button type="button" data-tab="library" class="tab">My Library</button>
        <button type="button" data-tab="coach" class="tab">Academic Mentor</button>
        <button type="button" data-tab="resources" class="tab">IELTS Resources</button>
        <button type="button" data-tab="videos" class="tab">YouTube Class</button>
        <button type="button" data-tab="speaking" class="tab">Speaking &amp; Voice</button>
        <button type="button" data-tab="mocks" class="tab">Mini Mock Tests</button>
      </nav>

      <section id="dashboardPanel" class="panel">
        <div class="section-heading"><div><div class="eyebrow">PROGRESS OVERVIEW</div><h2>Your writing journey</h2></div><span class="pill">Task 2 · AI estimate</span></div>
        <div class="stats">
          <article class="stat card"><span>Completed attempts</span><strong id="attempts">0</strong><small>Saved evaluations</small></article>
          <article class="stat card"><span>Latest estimate</span><strong id="latestScore">—</strong><small>Not an official band</small></article>
          <article class="stat card"><span>Focus for next session</span><strong id="weakestSkill" class="small-stat">Start practicing</strong><small>From practice history</small></article>
        </div>
        <div class="card content-card"><h3>Recent practice history</h3><div id="historyList" class="history-list"><p class="muted">No attempts yet.</p></div></div>
        <div class="card content-card"><h3>Account privacy</h3><p class="muted">নিজের অ্যাকাউন্ট এবং সংরক্ষিত practice history মুছে দিতে পারো। এই কাজ ফেরানো যাবে না।</p><form id="deleteForm"><label for="deletePassword">Confirm password to delete your account<input type="password" id="deletePassword" autocomplete="current-password" minlength="10" required placeholder="Enter password to delete account"></label><button type="submit" class="secondary">Delete my account and data</button></form></div>
      </section>

      <section id="lessonsPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">LEARN THE FUNDAMENTALS</div><h2>Study Materials + Ask AI</h2></div><span class="pill">Original lessons</span></div>
        <div class="lesson-layout">
          <aside class="card lesson-menu"><h3>Topics</h3><div id="lessonList"></div></aside>
          <article class="card lesson-reader"><div class="eyebrow" id="lessonSource">LESSON</div><h3 id="lessonTitle">Select a topic</h3><p id="lessonBody" class="lesson-prose"></p><div class="bangla-tip"><strong>বাংলা টিপস</strong><p id="lessonTip"></p></div><hr><h3>Ask / Search Knowledge</h3><p class="muted" id="teacherModeNotice">লেসনের কোনো বিষয় বুঝতে না পারলে প্রশ্ন করো। AI বন্ধ থাকলে নিজস্ব লেসন থেকে Study Guide দেওয়া হবে।</p>
            <form id="askForm"><label for="lessonQuestion" class="sr-only">Your question</label><textarea id="lessonQuestion" rows="3" maxlength="1200" placeholder="উদাহরণ: Topic sentence কীভাবে লিখব?" required></textarea><button type="submit" id="askButton" class="primary">Search Study Guide</button></form>
            <div id="lessonAnswer" class="answer-box hidden" aria-live="polite"></div>
          </article>
        </div>
      </section>

      <section id="writingPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">WRITING PRACTICE LAB</div><h2>IELTS Writing · Task 2</h2></div><span class="pill">Suggested time: 40 min · always free</span></div>
        <div class="exam-grid">
          <article class="card question-panel"><div class="eyebrow">TASK INSTRUCTIONS</div><h3>Essay question</h3><label>Choose a question<select id="promptSelect"></select></label><div class="question-box" id="writingQuestion">Question appears here.</div><p class="muted">Write at least 250 words. Support your position with relevant arguments and examples.</p><div class="timer-area"><span class="timer" id="timer">40:00</span><button type="button" id="timerToggle" class="secondary">Start timer</button><button type="button" id="timerReset" class="text-btn">Reset</button></div></article>
          <article class="card editor-panel"><div class="editor-top"><h3>Your answer</h3><span id="wordCount" class="word-count">0 words</span></div><label for="essay" class="sr-only">Write your Task 2 essay</label><textarea id="essay" class="essay-input" spellcheck="true" placeholder="Write your introduction here..." aria-label="Essay writing area"></textarea><div class="editor-foot"><span class="muted" id="saveNotice">Offline mode saves word/paragraph counts, not essay text.</span><button type="button" id="evaluateBtn" class="primary">Review structure (free)</button></div></article>
        </div>
        <section id="feedbackPanel" class="feedback-area hidden" aria-live="polite">
          <div class="section-heading"><div><div class="eyebrow">YOUR PERSONAL FEEDBACK</div><h2>Writing Practice Report</h2></div><span class="pill">Practice estimate only</span></div>
          <div class="report-grid"><div class="card score-summary"><span id="scoreKind">Writing practice report · Not an official band</span><strong id="overallScore">—</strong><p id="feedbackSummary"></p></div><div class="card criteria-card"><h3>Criteria breakdown</h3><div id="criterionScores"></div></div></div>
          <div class="report-grid"><article class="card content-card"><h3>What worked</h3><ul id="strengths"></ul></article><article class="card content-card"><h3>What to improve</h3><ul id="improvements"></ul></article></div>
          <article class="card content-card"><h3>Grammar & language fixes</h3><div id="grammarFixes"></div></article>
          <article class="card content-card"><h3>Improved sample essay</h3><p class="muted">শেখার উদাহরণ হিসেবে ব্যবহার করো; মুখস্থ করার জন্য নয়।</p><div id="revision" class="sample-essay"></div></article>
          <div class="next-step"><strong>Your next step</strong><p id="nextStep"></p></div>
        </section>
      </section>

      <section id="libraryPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">PRIVATE KNOWLEDGE LIBRARY</div><h2>Upload your learning materials</h2></div><span class="pill">Free · Private by default</span></div>
        <div class="report-grid">
          <article class="card content-card">
            <h3>PDF / JPG / PNG / TXT Upload</h3>
            <p class="muted">তোমার নিজের বা ব্যবহারের অনুমতি থাকা শিক্ষামূলক ফাইল আপলোড করো। ফাইলের মূল কপি রাখা হবে না; কেবল বের করা টেক্সট ব্যক্তিগত Knowledge Base-এ সেভ হবে। সর্বোচ্চ 8 MB।</p>
            <form id="uploadForm">
              <label>Lesson / book title<input id="uploadTitle" maxlength="200" placeholder="e.g. My IELTS Reading Notes" required></label>
              <label>Choose a file<input type="file" id="uploadFile" accept=".pdf,.jpg,.jpeg,.png,.txt,application/pdf,image/jpeg,image/png,text/plain" required></label>
              <label class="checkline"><input type="checkbox" id="uploadRights" required>এই উপকরণ সংরক্ষণ ও ব্যক্তিগত শিক্ষার জন্য প্রসেস করার অধিকার/অনুমতি আমার আছে।</label>
              <label class="checkline"><input type="checkbox" id="uploadAIConsent">AI চালু হলে এই নোটের প্রয়োজনীয় অংশ external AI provider-এ পাঠানো যাবে (ঐচ্ছিক)।</label>
              <button id="uploadButton" type="submit" class="primary">Upload &amp; Index</button>
            </form>
            <p class="fineprint">Text PDF স্বয়ংক্রিয়ভাবে পড়তে PDF extractor প্রয়োজন। ছবি ও scanned PDF-এর জন্য OCR প্রয়োজন। cPanel-এ এগুলো না থাকলে নিচে text paste করার ব্যবস্থা আছে।</p>
          </article>
          <article class="card content-card">
            <h3>Your uploaded documents</h3>
            <p class="muted">তোমার ফাইল শুধু তোমার account-এর knowledge search-এ পাওয়া যাবে।</p>
            <div id="documentList" class="history-list"><p class="muted">No files yet.</p></div>
            <div id="pasteBox" class="hidden">
              <h3 id="pasteTitle">Add extracted text</h3>
              <p class="muted">যে JPG বা scanned PDF-এ OCR হয়নি, সেটির টেক্সট নিজে paste করে ব্যক্তিগত Knowledge Base-এ যুক্ত করো।</p>
              <textarea id="pasteText" rows="7" maxlength="90000" placeholder="Paste study text..." aria-label="Text extracted from uploaded file"></textarea>
              <button type="button" class="secondary" id="pasteSave">Index pasted text</button>
            </div>
          </article>
        </div>
      </section>


      <section id="resourcesPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">OFFICIAL & INDEPENDENT VERIFIED LINKS</div><h2>IELTS learning resource directory</h2></div><span class="pill">Navigation · No scraping</span></div>
        <article class="card content-card"><p class="muted">এখানকার লিংকগুলো পাবলিক সাইটে নিয়ে যাবে। আমরা অন্য সাইটের সম্পূর্ণ লেখা বা Cambridge বই কপি করছি না। উৎসের নিয়ম মেনে নিজস্ব বা অনুমোদিত নোট Knowledge Library-তে যোগ করা যাবে।</p>
          <h3>Band 9 লক্ষ্য ধরে প্রস্তুতি</h3>
          <ol><li>প্রথমে চার দক্ষতায় একটি করে নমুনা কাজ করো। কোন প্রশ্নে কেন আটকালে লিখে রাখো।</li>
          <li>প্রতিদিন একটি দুর্বলতা নিয়ে কাজ করো। Listening-এ আগে একবার শুনে উত্তর দাও, তারপর উত্তর ও ট্রান্সক্রিপ্ট মিলিয়ে কঠিন অংশটি আবার শোনো।</li>
          <li>Writing-এর একটি খসড়া সংশোধন করো। Speaking-এর একই প্রশ্নের উত্তর আবার রেকর্ড করে উদাহরণ ও স্পষ্টতা তুলনা করো।</li>
          <li>প্রতি সপ্তাহে নতুন প্রশ্নে সময় ধরে অনুশীলন করো। আগের ভুলগুলো কতবার ফিরেছে দেখে পরের সপ্তাহের লক্ষ্য ঠিক করো।</li></ol>
          <label>Filter by skill<select id="resourceSkill"><option value="all">All IELTS skills</option><option value="reading">Reading</option><option value="listening">Listening</option><option value="writing">Writing</option><option value="speaking">Speaking</option></select></label>
          <div id="resourceList" class="resource-grid"></div>
        </article>
      </section>

      <section id="videosPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">LEARN FROM A CLASS</div><h2>YouTube Lesson Companion</h2></div><span class="pill">User-provided links</span></div>
        <div class="report-grid">
          <article class="card content-card">
            <p class="muted">YouTube video link দিলে player-এ ক্লাস দেখতে পারবে। AI ভিডিওটি নিজে থেকে শোনে না বা transcript সংগ্রহ করে না—ক্লাসের নিজের নোট যোগ করলে সেই নোট থেকে গাইড করা যাবে।</p>
            <form id="videoForm">
              <label>HTTPS YouTube video link<input id="videoUrl" type="url" maxlength="600" required placeholder="https://www.youtube.com/watch?v=..."></label>
              <label>Lesson title<input id="videoTitle" maxlength="200" required placeholder="Reading: Matching Headings"></label>
              <label>My class notes (optional)<textarea id="videoNotes" rows="7" maxlength="12000" placeholder="ক্লাস দেখে নিজের ভাষায় নোট লিখো... "></textarea></label>
              <label class="checkline"><input id="videoAiConsent" type="checkbox">AI চালু থাকলে আমার এই নোট external AI-তে পাঠাতে সম্মতি দিচ্ছি (ঐচ্ছিক)।</label>
              <button class="primary" type="submit" id="videoSave">Save class</button>
            </form>
          </article>
          <article class="card content-card"><h3>My saved classes</h3><div id="videoList" class="history-list"></div>
            <div id="videoPlayerWrap" class="hidden"><div class="video-player"><iframe id="videoPlayer" title="YouTube class video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe></div><p><a id="videoWatch" target="_blank" rel="noopener noreferrer" href="#">Watch on YouTube</a></p></div>
          </article>
        </div>
      </section>

      <section id="speakingPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">ORIGINAL SPEAKING PRACTICE</div><h2>Speaking + Voice Message</h2></div><span class="pill">Not an official examiner</span></div>
        <div class="report-grid">
          <article class="card content-card">
            <p class="muted">Part 1, cue card (১ মিনিট preparation), Part 3। AI ছাড়া Browser speech synthesis প্রশ্ন পড়ে শোনাবে এবং সমর্থিত ব্রাউজারে speech-to-text করবে। টাইপ করেও উত্তর দেওয়া যাবে।</p>
            <div class="speaking-meta"><strong id="speakStage">Start a session</strong><span id="speakClock" class="timer">—</span></div>
            <div id="speakPrompt" class="question-box">Press Start to practise speaking.</div>
            <div class="control-row"><button id="speakStart" class="primary" type="button">Start speaking practice</button><button id="speakRead" class="secondary" type="button">Read question aloud</button><button id="speakNext" class="secondary" type="button">Next question</button></div>
            <label>Your answer / dictated message<textarea id="speakTranscript" rows="6" maxlength="6000" placeholder="Voice-to-text output appears here, or type your answer..."></textarea></label>
            <div class="control-row"><button id="speakMic" class="secondary" type="button">🎙 Dictate answer</button><button id="speakSubmit" class="primary" type="button">Save answer & review</button></div>
            <div id="speakFeedback" class="answer-box hidden" aria-live="polite"></div>
          </article>
          <article class="card content-card"><h3>Voice message recorder</h3><p class="muted">Record a voice message to listen to your own answer. Recording stays in your browser; only transcript is saved when you choose Save. Browser speech recognition may send audio to its provider.</p>
            <div class="control-row"><button id="recordStart" type="button" class="secondary">Start recording</button><button id="recordStop" type="button" class="secondary" disabled>Stop recording</button></div>
            <audio id="voicePreview" controls class="hidden audio-preview"></audio>
            <h3>Recent speaking practice</h3><div id="speakingHistory" class="history-list"></div>
          </article>
        </div>
      </section>

      <section id="mocksPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">FREE PRACTICE LAB</div><h2>Reading & Listening Mini Mocks</h2></div><span class="pill">Original questions · No band score</span></div>
        <div class="card content-card"><p class="muted">এই পরীক্ষাগুলো আমাদের নিজস্ব ছোট practice quiz; Cambridge বা পূর্ণ 40-question IELTS test নয়। Listening audio হলো browser-generated spoken text, আসল IELTS recording নয়। Writing এবং Speaking আলাদা ট্যাবে অনুশীলন করা যাবে।</p>
          <label>Choose mini mock<select id="mockSelect"></select></label>
          <div class="control-row"><button id="mockStart" type="button" class="primary">Start mini mock</button><span id="mockClock" class="timer">—</span></div>
        </div>
        <div id="mockExam" class="exam-grid hidden">
          <div class="card content-card"><h3 id="mockTitle">Question passage</h3><div id="mockPassage" class="lesson-prose"></div><div id="mockAudio" class="hidden"><p class="muted">প্রথমে Play চাপো, তারপর প্রশ্নের উত্তর দাও।</p><div class="control-row"><button class="secondary" id="mockListen" type="button">▶ Play listening audio</button><button class="secondary" id="mockStopAudio" type="button">Stop audio</button></div></div></div>
          <div class="card content-card"><form id="mockForm"><div id="mockQuestions"></div><button id="mockSubmit" class="primary" type="submit">Submit answers</button></form></div>
        </div>
        <div id="mockResults" class="card content-card hidden" aria-live="polite"></div>
        <div class="card content-card"><h3>Recent mini mock scores</h3><div id="mockHistory"></div></div>
      </section>

      <section id="coachPanel" class="panel hidden">
        <div class="section-heading"><div><div class="eyebrow">STUDENT ACADEMIC MENTOR</div><h2>Ask your IELTS &amp; Study Mentor</h2></div><span class="pill">English · IELTS · Academics</span></div>
        <article class="card content-card">
          <p class="muted">IELTS, ইংরেজি, পরীক্ষা, পড়াশোনার রুটিন, ভর্তি, স্কলারশিপ বা study-abroad planning নিয়ে প্রশ্ন করো। অপ্রাসঙ্গিক বিষয়ে এই mentor উত্তর দেবে না। AI বন্ধ থাকলে শুধু সংরক্ষিত লেসন ও ব্যক্তিগত নোট খুঁজে দেখাবে।</p>
          <form id="coachForm">
            <label>Subject<select id="coachModule"><option value="general">General study &amp; academic planning</option><option value="reading">Reading</option><option value="writing">Writing</option><option value="listening">Listening</option><option value="speaking">Speaking</option></select></label>
            <label>Source<select id="coachDoc"><option value="">All approved study lessons + my documents</option></select></label>
            <label>My question<textarea id="coachQuestion" rows="5" maxlength="1200" placeholder="উদাহরণ: Reading-এ আমি বারবার Not Given ভুল করি, কীভাবে উন্নতি করব?" required></textarea></label>
            <button class="primary" id="coachButton" type="submit">Ask Mentor</button>
          </form>
          <div id="coachAnswer" class="answer-box hidden" aria-live="polite"></div>
          <div class="fineprint" id="coachModeNote">AI না থাকলে local knowledge search চলবে; ব্যক্তিগত পরামর্শের জন্য নিয়মমাফিক AI provider প্রয়োজন।</div>
        </article>
      </section>
    </div>
    <div id="toast" role="status" class="toast hidden"></div>
  </main>
  <footer>Free and non-commercial learning project · Not affiliated with official IELTS · <a href="privacy.html">Privacy</a></footer>
</body>
</html>
