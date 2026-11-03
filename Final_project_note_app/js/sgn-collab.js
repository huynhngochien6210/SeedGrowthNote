/* SeedGrowthNote: note password, share, shared notes and collaborative editing */
(() => {
  'use strict';
  const ROOT = 'api/collab/';
  const csrf = document.querySelector('meta[name="sgn-csrf"]')?.content || '';
  const $ = id => document.getElementById(id);
  const state = { noteId: null, recipients: [], bypass: new Set(), shared: null, saveTimer: null, loading: false };
  async function api(file, data, query='') {
    const options = { credentials:'same-origin', headers: {Accept:'application/json'} };
    if (data !== undefined) {
      options.method='POST'; options.headers['Content-Type']='application/json';
      options.headers['X-CSRF-Token']=csrf; options.body=JSON.stringify(data);
    }
    const res = await fetch(ROOT+file+(query?'?'+new URLSearchParams(query):''), options);
    const json = await res.json().catch(()=>({success:false,message:'Máy chủ không trả về JSON'}));
    if (!res.ok || !json.success) throw Object.assign(new Error(json.message||'Request failed'),{status:res.status,code:json.code});
    return json;
  }
  const info = e => window.alert(e?.message||String(e));
  function currentCard() { return document.querySelector(`.note-card[data-note-id="${CSS.escape(String(state.noteId))}"]`); }
  async function unlock(noteId) {
    const status=await api('password.php',undefined,{note_id:noteId});
    if (!status.protected || status.unlocked) return true;
    const password=prompt('Ghi chú được bảo vệ. Nhập mật khẩu để mở:');
    if (password===null) return false;
    await api('password.php',{note_id:noteId,action:'unlock',password});
    return true;
  }
  async function managePassword(action, noteId) {
    if (!noteId) return;
    const data={note_id:noteId,action};
    if (action==='enable' || action==='change') {
      if(action==='change') {
        data.current_password=prompt('Mật khẩu hiện tại:');
        if (data.current_password===null) return false;
      }
      data.new_password=prompt('Mật khẩu mới (ít nhất 8 ký tự):');
      if (data.new_password===null) return false;
      if (data.new_password!==prompt('Nhập lại mật khẩu mới:')) {alert('Mật khẩu xác nhận không khớp.');return false;}
    } else if(action==='disable') {
      data.current_password=prompt('Nhập mật khẩu hiện tại để bỏ bảo vệ:');
      if (data.current_password===null) return false;
    }
    const result=await api('password.php',data);
    const card=document.querySelector(`.note-card[data-note-id="${CSS.escape(String(noteId))}"]`);
    if (card) card.dataset.protected=String(result.protected);
    if ($('passwordNoteToggle')) $('passwordNoteToggle').checked=!!result.protected;
    if ($('notePasswordArea')) $('notePasswordArea').hidden=true;
    if($('sgnChangeNotePassword')) $('sgnChangeNotePassword').hidden=!result.protected;
    alert(result.message);
    return true;
  }
  function renderRecipients() {
    const list=$('sharePeopleList'), empty=$('shareEmpty'), confirm=$('shareConfirmBtn');
    if (!list) return;
    list.replaceChildren();
    for (const recipient of state.recipients) {
      const row=document.createElement('div'); row.className='share-person-item';
      const email=document.createElement('span'); email.className='share-person-email'; email.textContent=recipient.email;
      const permission=document.createElement('select'); permission.className='form-select share-person-select';
      for(const [value,label] of [['read','Can view'],['edit','Can edit']]) {
        const opt=new Option(label,value); permission.add(opt);
      }
      permission.value=recipient.permission;
      permission.onchange=()=>recipient.permission=permission.value;
      const remove=document.createElement('button');remove.type='button';remove.className='share-person-remove';remove.textContent='×';remove.title='Revoke access';
      remove.onclick=()=>{state.recipients=state.recipients.filter(v=>v!==recipient);renderRecipients();};
      row.append(email,permission,remove);list.append(row);
    }
    if (empty) empty.hidden=state.recipients.length>0;
    if (confirm) confirm.disabled=false; // zero recipients = revoke all
  }
  function setShareError(text){if($('shareError')) $('shareError').textContent=text;}
  async function openShare(noteId){
    try {
      state.noteId=String(noteId);
      const data=await api('shares.php',undefined,{note_id:noteId});
      state.recipients=data.data||[];
      if($('shareUserInput')) $('shareUserInput').value='';
      setShareError('');renderRecipients();
      bootstrap.Modal.getOrCreateInstance($('shareNoteModal')).show();
    } catch(e){info(e);}
  }
  async function loadShared(){
    const grid=$('sharedGrid');if(!grid)return;
    grid.replaceChildren();
    try {
      const result=await api('shared_notes.php');
      if (!result.data.length){grid.textContent='Chưa có ghi chú nào được chia sẻ.';return;}
      for(const n of result.data){
        const card=document.createElement('button');card.type='button'; card.className='shared-note-card sgn-shared-clickable';
        const title=document.createElement('strong');title.textContent=n.title||'Untitled';
        const details=document.createElement('span');details.textContent=`${n.sharer_email} · ${n.permission==='edit'?'Can edit':'Can view'}${Number(n.is_password_protected)?' · 🔒':''}`;
        card.append(title,details);card.onclick=()=>openShared(n.id);grid.append(card);
      }
    } catch(e){grid.textContent=e.message;}
  }
  // Minimal allowlist for rich text received from another user.
  function safeRichHtml(html) {
    const doc=new DOMParser().parseFromString(String(html||''),'text/html');
    const allowed=new Set(['P','DIV','BR','SPAN','B','STRONG','I','EM','U','UL','OL','LI','H1','H2','H3','BLOCKQUOTE','A']);
    function clean(node) {
      for(const child of Array.from(node.children)) {
        if(!allowed.has(child.tagName)) {
          if(['SCRIPT','STYLE','IFRAME','OBJECT','EMBED','SVG','MATH','FORM'].includes(child.tagName)) {child.remove();continue;}
          clean(child);child.replaceWith(...Array.from(child.childNodes));continue;
        }
        for(const attr of Array.from(child.attributes)) {
          if(attr.name!=='href' || child.tagName!=='A') {child.removeAttribute(attr.name);continue;}
          try {const url=new URL(attr.value,location.href);if(!['https:','http:','mailto:'].includes(url.protocol)) child.removeAttribute('href');}
          catch {child.removeAttribute('href');}
        }
        clean(child);
      }
    }
    clean(doc.body);return doc.body.innerHTML;
  }
  function ensureSharedEditor(){
    let panel=$('sgnSharedEditor');if(panel)return panel;
    panel=document.createElement('section');panel.id='sgnSharedEditor';panel.className='sgn-shared-editor';panel.hidden=true;
    const top=document.createElement('div');top.className='sgn-shared-top';
    const back=document.createElement('button');back.type='button';back.textContent='← Back';back.onclick=async()=>{
      if(state.saveTimer){
        clearTimeout(state.saveTimer);state.saveTimer=null;
        if(state.shared && state.shared.permission!=='read'){
          try{await api('update_shared.php',{note_id:state.shared.id,title:$('sgnSharedTitle').value,content:$('sgnSharedContent').innerHTML});}
          catch(e){info(e);return;}
        }
      }
      state.shared=null;panel.hidden=true; $('sharedBoardView')?.classList.remove('d-none');loadShared();
    };
    const badge=document.createElement('span');badge.id='sgnSharedPermission';
    const status=document.createElement('span');status.id='sgnSharedSaveStatus';
    top.append(back,badge,status);
    const title=document.createElement('input');title.id='sgnSharedTitle';title.placeholder='Title';
    const content=document.createElement('div');content.id='sgnSharedContent';content.className='note-content';content.setAttribute('role','textbox');content.setAttribute('aria-label','Shared note content');
    panel.append(top,title,content);document.querySelector('.app-main-inner')?.append(panel);
    const queue=()=>{
      if(!state.shared || state.shared.permission==='read')return;
      clearTimeout(state.saveTimer);status.textContent='Saving...';
      state.saveTimer=setTimeout(async()=>{
        const id=state.shared?.id;if(!id)return;
        try {await api('update_shared.php',{note_id:id,title:title.value,content:content.innerHTML});status.textContent='Saved';}
        catch(e){status.textContent='Save failed: '+e.message;}
      },750);
    };
    title.addEventListener('input',queue);content.addEventListener('input',queue);
    return panel;
  }
  async function openShared(noteId){
    try {
      if (!await unlock(noteId)) return;
      const result=await api('shared_note.php',undefined,{note_id:noteId});const n=result.data;
      state.shared=n;const panel=ensureSharedEditor();
      $('sharedBoardView')?.classList.add('d-none');
      document.querySelectorAll('.app-panel.active').forEach(p=>p.classList.remove('active'));
      $('sgnSharedTitle').value=n.title;
      $('sgnSharedContent').innerHTML=safeRichHtml(n.content);
      const editable=n.permission==='edit'||n.permission==='owner';
      $('sgnSharedTitle').readOnly=!editable;
      $('sgnSharedContent').contentEditable=String(editable);
      $('sgnSharedPermission').textContent=editable?'Can edit':'Read only';
      $('sgnSharedSaveStatus').textContent='';panel.hidden=false;
    }catch(e){info(e);}
  }
  function init(){
    if(!$('noteGrid'))return;
    // Prevent opening a protected note with redacted content.
    document.addEventListener('click',async e=>{
      const link=e.target.closest('#noteGrid .note-card-link');if(!link)return;
      const card=link.closest('.note-card');if(!card || card.dataset.protected!=='true')return;
      const id=String(card.dataset.noteId);
      if(state.bypass.has(id)){state.bypass.delete(id);return;}
      e.preventDefault();e.stopImmediatePropagation();
      try {
        if (!await unlock(id))return;
        const n=(await api('shared_note.php',undefined,{note_id:id})).data;
        card.dataset.editorContent=n.content||'';
        state.bypass.add(id);link.click();
      }catch(err){info(err);}
    },true);
    document.addEventListener('click',e=>{
      const link=e.target.closest('#noteGrid .note-card-link');if(!link)return;
      const card=link.closest('.note-card'); if(!card)return;
      state.noteId=String(card.dataset.noteId);
      setTimeout(()=>{if($('passwordNoteToggle'))$('passwordNoteToggle').checked=card.dataset.protected==='true';if($('notePasswordArea'))$('notePasswordArea').hidden=true;if($('sgnChangeNotePassword'))$('sgnChangeNotePassword').hidden=card.dataset.protected!=='true';},0);
    });
    document.addEventListener('seedgrowth:note-created',e=>{state.noteId=String(e.detail?.card?.dataset.noteId||'');});
    document.addEventListener('seedgrowth:note-action',e=>{
      const {action,noteId}=e.detail||{};
      if(action==='share')openShare(noteId);
      if(action==='lock'){
        state.noteId=String(noteId);
        api('password.php',undefined,{note_id:noteId}).then(s=>managePassword(s.protected?'change':'enable',noteId)).catch(info);
      }
    });
    const toggle=$('passwordNoteToggle');
    toggle?.addEventListener('change',async()=>{
      const card=currentCard(),id=card?.dataset.noteId||state.noteId;
      if(!id)return;
      const prior=card?.dataset.protected==='true';
      try{const result=await managePassword(toggle.checked?'enable':'disable',id);if(!result)toggle.checked=prior;}
      catch(e){toggle.checked=prior;info(e);}
    });
    // Existing password input placeholder remains; user actions use prompts instead.
    const parent=$('notePasswordArea');if(parent){
      const change=document.createElement('button');change.type='button';change.className='btn btn-outline-secondary btn-sm';change.textContent='Change note password';
      change.onclick=async()=>{try{await managePassword('change',state.noteId);}catch(e){info(e);}};
      parent.insertAdjacentElement('afterend',change);change.id='sgnChangeNotePassword';change.hidden=true;
    }
    const editPanel=$('noteEditorAppPanel');if(editPanel){
      new MutationObserver(()=>{$('sgnChangeNotePassword') && ($('sgnChangeNotePassword').hidden=!(editPanel.classList.contains('active') && currentCard()?.dataset.protected==='true'));})
        .observe(editPanel,{attributes:true,attributeFilter:['class']});
    }
    $('shareAddBtn')?.addEventListener('click',()=>{
      const input=$('shareUserInput');const email=input.value.trim().toLowerCase();const permission=$('shareDefaultPermission').value;
      if(!/^\S+@\S+\.\S+$/.test(email)){setShareError('Email không hợp lệ');return;}
      if(state.recipients.some(r=>r.email.toLowerCase()===email)){setShareError('Người này đã có trong danh sách');return;}
      state.recipients.push({email,permission});input.value='';setShareError('');renderRecipients();
    });
    $('shareUserInput')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();$('shareAddBtn')?.click();}});
    $('shareConfirmBtn')?.addEventListener('click',async()=>{
      const button=$('shareConfirmBtn');button.disabled=true;
      try {const r=await api('shares.php',{note_id:state.noteId,shares:state.recipients});
        bootstrap.Modal.getOrCreateInstance($('shareNoteModal')).hide();alert(r.message);
      }catch(e){setShareError(e.message);}finally{button.disabled=false;}
    });
    // Replace old mock data by actual API data.
    $('shareWithBtn')?.addEventListener('click',()=>setTimeout(()=>{
      if(!$('sharedBoardView')?.classList.contains('d-none')) loadShared();
    },0));
    // Existing shared panel navigation is handled by shareWithMe.js.
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
