// Menu note
document.addEventListener('DOMContentLoaded', () =>{
  const menuBtn= document.getElementById('newNoteTrigger');
  const menu= document.getElementById('newNoteMenu');
  const btnCreateNote= document.getElementById('btnCreateNote');
  const btnUploadFile= document.getElementById('btnUploadFile');
  const hiddenFileInput= document.getElementById('hiddenFileInput');
  const template= document.getElementById('noteTemplate');
  
  const noteGrid = document.getElementById('noteGrid');

  

  // Load notes from database
  async function loadNotes() {
      try {
          const response = await fetch('/api/notes/get_notes.php', {
              method: 'GET',
              credentials: 'same-origin',
              headers: {
                  'Accept': 'application/json'
              }
          });

          const result = await response.json();

          if (!response.ok || !result.success) {
              throw new Error(result.message || 'Failed to load notes');
          }

          // Remove old note cards, keep the Create Note card
          noteGrid.querySelectorAll('.note-card').forEach(card => {
              card.remove();
          });

          const newNoteCard = noteGrid.querySelector('.new-note-card');

          result.data.forEach(note => {
              const clone = template.content.cloneNode(true);

              const card = clone.querySelector('.note-card');
              const link = clone.querySelector('.note-card-link');
              const title = clone.querySelector('.note-card-title');
              const date = clone.querySelector('.note-card-date');

              // Note information
              card.dataset.noteId = note.id;
              link.dataset.noteId = note.id;

              title.textContent = note.title || 'Untitled';
              date.textContent = formatNoteDate(note.updated_at);

              // Add note card after Create Note card
              newNoteCard.after(clone);
          });

      } catch (error) {
          console.error('Load notes error:', error);
          console.error('Message:', error.message);
      }
  }

  // Format date
  function formatNoteDate(dateString) {
      if (!dateString) return '';

      const date = new Date(dateString.replace(' ', 'T'));

      if (Number.isNaN(date.getTime())) return '';

      return date.toLocaleDateString('vi-VN');
  }

  // Load notes when page is ready
  loadNotes();

  const panels = document.querySelectorAll('.app-panel');
  const sidebar = document.querySelector('.app-sidebar');
  const noteBoard = document.querySelector('.note-board');
  
  // mo menu
  menuBtn.addEventListener('click', (e) =>{
    e.stopPropagation();
    const isOpening= menu.hidden;
    menu.hidden= !menu.hidden;
    if(isOpening){
      const rect= menuBtn.getBoundingClientRect();
      menu.style.top= `${rect.bottom - 6}px`;
      menu.style.left= `${rect.left}px`;
    }
  })
  // Bam ra ngoai de dong
  document.addEventListener('click', (e) =>{
    if(!menu.hidden && !menu.contains(e.target) && e.target !== menuBtn){
      menu.hidden= true;
    }
  })
  // Choose color seed
  const popover= document.getElementById('colorPopover');
  let activeIcon= null; 
  function openPopover(iconEl){
    activeIcon= iconEl;
    const rect= iconEl.getBoundingClientRect();
    popover.hidden= false;
    popover.style.top= `${rect.top + window.scrollY - popover.offsetHeight - 8}px`;
    popover.style.left= `${rect.left + window.scrollX + rect.width/2 - popover.offsetWidth/2}px`;
  };
  function closePopover(){
    popover.hidden= true;
    activeIcon= null;
  };
  // Bam vao icon bat ky de mo
  document.getElementById('noteGrid').addEventListener('click', (e) =>{
    const icon= e.target.closest('.note-card-icon.is-clickable');
    if(!icon) return;
    e.stopPropagation();
    openPopover(icon);
  });
  //Bam chon mau
  popover.addEventListener('click', (e) =>{
    const swatch= e.target.closest('.color-swatch');
    if(!swatch || !activeIcon) return;
    activeIcon.style.setProperty('--seed-body', swatch.dataset.color);
    closePopover();
  })
  //Bam ra ngoai de dong 
  document.addEventListener('click', (e) =>{
    if(!popover.hidden && !popover.contains(e.target)){
      closePopover();
    }
  })
  // Option 1: Create note and save to database
  btnCreateNote.addEventListener('click', async (e) => {
    e.stopPropagation();
    menu.hidden = true;

    try {
        const response = await fetch('/api/notes/create_note.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to create note');
        }

        const note = result.data;

        // Create note card from template
        const clone = template.content.cloneNode(true);

        const card = clone.querySelector('.note-card');
        const link = clone.querySelector('.note-card-link');
        const title = clone.querySelector('.note-card-title');
        const date = clone.querySelector('.note-card-date');

        card.dataset.noteId = note.id;
        card.dataset.editorTitle = note.title || '';
        card.dataset.editorContent = note.content || '';
        card.dataset.pinned = String(note.is_pinned == 1);

        link.dataset.noteId = note.id;

        title.textContent = note.title || 'Untitled';
        date.textContent = formatNoteDate(note.updated_at);

        const newNoteCard = document.querySelector('.new-note-card');

        newNoteCard.after(clone);

        // Notify noteEditor.js to open the newly created note
        const createdCard = noteGrid.querySelector(
            `.note-card[data-note-id="${note.id}"]`
        );

        document.dispatchEvent(new CustomEvent('seedgrowth:note-created', {
            detail: { card: createdCard }
        }));

    } catch (error) {
        console.error('Create note error:', error);
        alert('Could not create note: ' + error.message);
    }
  });

  //Option 2: Upload file
  btnUploadFile.addEventListener('click', (e) =>{
    e.stopPropagation();
    menu.hidden= true;
    hiddenFileInput.click();
  })
  // Khi user da chon xong file
  hiddenFileInput.addEventListener('change', ()=>{
    const file= hiddenFileInput.files[0];
    if(!file) return;
    console.log('Chose file: ', file.name, file.size, 'bytes');
    // Todo khi co backend

    // Reset input để chọn lại cùng 1 file vẫn kích hoạt được sự kiện 'change' lần sau
    hiddenFileInput.value = '';
  })
})