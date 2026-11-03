// Menu note
document.addEventListener('DOMContentLoaded', () =>{
  const menuBtn= document.getElementById('newNoteTrigger');
  const menu= document.getElementById('newNoteMenu');
  const btnCreateNote= document.getElementById('btnCreateNote');
  const btnUploadFile= document.getElementById('btnUploadFile');
  const hiddenFileInput= document.getElementById('hiddenFileInput');
  const template= document.getElementById('noteTemplate');
  
  const noteGrid = document.getElementById('noteGrid');

  // Grid / List view
const viewButtons = document.querySelectorAll('.view-toggle-group-btn');

viewButtons.forEach(button => {
    button.addEventListener('click', () => {
        const view = button.dataset.view;

        // Update active button
        viewButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Change note layout
        if (view === 'list') {
            noteGrid.classList.add('list-view');
        } else {
            noteGrid.classList.remove('list-view');
        }
    });
});

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
              card.dataset.editorTitle = note.title || '';
              card.dataset.editorContent = note.content || '';
              card.dataset.pinned = String(note.is_pinned == 1);

              // Identify note type using database
            const attachment = note.attachments?.[0];

            card.dataset.noteType = note.note_type || 'text';

            if (note.note_type === 'file' && attachment) {
                card.dataset.filePath = attachment.file_path;
                card.dataset.fileName = attachment.file_name;
            }

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
        card.dataset.noteType = note.note_type || 'text';

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

  
  // Option 2: Upload file
  btnUploadFile.addEventListener('click', (e) => {
      e.stopPropagation();
      menu.hidden = true;
      hiddenFileInput.click();
  });

  // Upload file and create a new Note
  hiddenFileInput.addEventListener('change', async () => {
      const file = hiddenFileInput.files[0];

      if (!file) return;

      try {
          // Check file size (10MB)
          if (file.size > 10 * 1024 * 1024) {
              throw new Error('File size must not exceed 10MB.');
          }

          // Step 1: Create a new Note
          const createResponse = await fetch(
              '/api/notes/create_note.php',
              {
                  method: 'POST',
                  credentials: 'same-origin',
                  headers: {
                      'Accept': 'application/json'
                  }
              }
          );

          const createResult = await createResponse.json();

          if (!createResponse.ok || !createResult.success) {
              throw new Error(
                  createResult.message || 'Failed to create Note'
              );
          }

          const noteId = createResult.data.id;

          // Step 2: Set Note title to file name
          const updateResponse = await fetch(
              '/api/notes/update_note.php',
              {
                  method: 'POST',
                  credentials: 'same-origin',
                  headers: {
                      'Content-Type': 'application/json',
                      'Accept': 'application/json'
                  },
                  body: JSON.stringify({
                    note_id: noteId,
                    title: file.name,
                    content: '',
                    note_type: 'file'
                })
              }
          );

          const updateResult = await updateResponse.json();

          if (!updateResponse.ok || !updateResult.success) {
              throw new Error(
                  updateResult.message || 'Failed to update Note'
              );
          }

          // Step 3: Upload file to Attachments
          const formData = new FormData();

          formData.append('note_id', noteId);
          formData.append('attachment', file);

          const uploadResponse = await fetch(
              '/api/upload_file/attachments.php',
              {
                  method: 'POST',
                  credentials: 'same-origin',
                  body: formData
              }
          );

          const uploadResult = await uploadResponse.json();

          if (!uploadResponse.ok || !uploadResult.success) {
              throw new Error(
                  uploadResult.message || 'Failed to upload file'
              );
          }

          // Step 4: Reload Note Board
          await loadNotes();

          alert('File uploaded successfully!');

      } catch (error) {
          console.error('Upload file error:', error);
          alert('Upload failed: ' + error.message);

      } finally {
          hiddenFileInput.value = '';
      }
  });
})