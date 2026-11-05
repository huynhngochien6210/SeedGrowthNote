/* SeedGrowthNote: AI Summary and question answering against accessible MySQL notes. */
document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  const $ = (id) => document.getElementById(id);
  const panel = $('aiPanel'), toggle = $('aiToggle'), close = $('aiClose');
  const input = $('aiInput'), send = $('aiSend');
  if (!panel || !toggle || !input || !send) return;

  let chat = panel.querySelector('.ai-chat');

  if (!chat) {
      chat = document.createElement('div');
      chat.className = 'ai-chat';
      chat.style.flex = '1';
      chat.style.overflowY = 'auto';
      chat.style.minHeight = '0';

      const composer = input.closest('form')
          || input.parentElement;

      if (composer && panel.contains(composer)) {
          panel.insertBefore(chat, composer);
      } else {
          panel.appendChild(chat);
      }
  }
 
  const csrf = document.querySelector('meta[name="sgn-csrf"]')?.content || '';
  let busy = false;
  const say = (message, cls = 'ai-message') => {
    const node = document.createElement('div'); node.className = cls;
    node.style.whiteSpace = 'pre-wrap'; node.textContent = message;
    chat.append(node); chat.scrollTop = chat.scrollHeight;
    return node;
  };
  const open = () => {panel.style.display = 'flex'; toggle.style.display = 'none';};
  const hide = () => {panel.style.display = 'none'; toggle.style.display = 'flex';};
  toggle.addEventListener('click', open);
  close?.addEventListener('click', hide);

  async function callApi(path, body) {
    const res = await fetch('api/ai/' + path, {
      method: 'POST', credentials: 'same-origin',
      headers: {'Content-Type':'application/json','X-CSRF-Token':csrf,'Accept':'application/json'},
      body: JSON.stringify(body)
    });
    const data = await res.json().catch(() => ({message:'Server không trả về JSON'}));
    if (!res.ok || !data.success) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
  }
  function getCurrentNoteId() {
    return window.SeedGrowthNoteCurrentNoteId || null;
  }
  async function sendQuestion() {
    if (busy) return;
    const question = input.value.trim(); if (!question) return;
    busy = true; send.disabled = true; input.value = '';
    say(question, 'user-message'); const reply = say('Đang tìm trong các ghi chú được phép đọc...');
    try {
      const noteId = getCurrentNoteId();

      const data = await callApi('ask.php', {
          question,
          ...(noteId ? { note_id: Number(noteId) } : {})
      });
      
      reply.textContent = data.answer;
      for (const ref of data.citations || []) {
        const citation = document.createElement('div'); citation.className = 'sgn-ai-citation';
        citation.textContent = `📄 Note #${ref.note_id}: ${ref.title}`;
        chat.appendChild(citation);
      }
    } catch (e) {reply.textContent = 'Không thể trả lời: ' + e.message;}
    finally {busy = false;send.disabled = false;chat.scrollTop = chat.scrollHeight;}
  }
  send.addEventListener('click', sendQuestion);
  input.addEventListener('keydown', e => {if (e.key === 'Enter') {e.preventDefault();sendQuestion();}});

  // Add a separate Summary button to editor without rewriting the team's editor logic.
  const footer = document.querySelector('.note-editor-footer');
  if (footer && !$('sgnAiSummary')) {
    const button = document.createElement('button'); button.id = 'sgnAiSummary'; button.type = 'button';
    button.className = 'sgn-ai-summary-btn'; button.textContent = '✦ AI Summary';
    footer.append(button);
    button.addEventListener('click', async () => {
      if (busy) return;
      const editor = $('noteEditorWorkspace');
      if (editor && !editor.classList.contains('ne-open') && !$('noteEditorAppPanel')?.classList.contains('active')) {
        alert('Hãy mở ghi chú trước khi tóm tắt.'); return;
      }
      const noteId = document.querySelector('.note-card[data-note-id].sgn-active')?.dataset.noteId
        || document.querySelector('.note-card[data-note-id].active')?.dataset.noteId
        || window.SeedGrowthNoteCurrentNoteId
        || document.querySelector('#noteEditorWorkspace [data-note-id]')?.dataset.noteId;
      if (!noteId) {
        const entered = prompt('Nhập ID ghi chú đang mở để tóm tắt (xem data-note-id trên note card):');
        if (!entered) return;
        await summarize(entered); return;
      }
      await summarize(noteId);
    });
  }
  async function summarize(noteId) {
    if (busy) return;
    busy = true;open();send.disabled = true;
    const reply = say(`Đang tạo bản tóm tắt cho note #${noteId}...`);
    try {
      const data = await callApi('summary.php', {note_id:Number(noteId)});
      reply.textContent = `📝 Bản tóm tắt của note #${noteId}:\n\n${data.summary}`;
    } catch(e) {reply.textContent = 'Không thể tóm tắt: ' + e.message;}
    finally {busy = false;send.disabled = false;}
  }
});
