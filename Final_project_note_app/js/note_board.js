// Menu note
document.addEventListener('DOMContentLoaded', () =>{
  const menuBtn= document.getElementById('newNoteTrigger');
  const menu= document.getElementById('newNoteMenu');
  const btnCreateNote= document.getElementById('btnCreateNote');
  const btnUploadFile= document.getElementById('btnUploadFile');
  const hiddenFileInput= document.getElementById('hiddenFileInput');
  const template= document.getElementById('noteTemplate');
  
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
  // Option 1: Create note
  const newNoteCard= document.querySelector('.new-note-card');
  btnCreateNote.addEventListener('click', (e) =>{
    e.stopPropagation();

    const clone= template.content.cloneNode(true);
    const now= new Date();

    clone.querySelector('.note-card-title').textContent= 'Untitle';
    clone.querySelector('.note-card-date').textContent= now.toLocaleDateString('vi-VN');
    newNoteCard.after(clone);
    menu.hidden= true;
  })
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