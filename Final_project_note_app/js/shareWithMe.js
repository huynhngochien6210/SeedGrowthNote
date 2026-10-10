function renderSharedNote(data){
    const template= document.getElementById('sharedNoteTemplate');
    const clone= template.content.cloneNode(true);
    const card= clone.querySelector('.shared-note-card');

    card.querySelector('.shared-note-title').textContent= data.title || 'Untitile';

    // Ngày nhận — chỉ hiện nếu có dữ liệu thật
    const receivedEl = card.querySelector('.shared-note-received');
    if (data.receivedDate) {
        receivedEl.textContent = `Recieved : ${data.receivedDate}`;
    } else {
        receivedEl.remove();   // không có dữ liệu → xoá hẳn khỏi DOM, không để trống rỗng
    }

    // Người share — chỉ hiện nếu có email
    const sharerEl = card.querySelector('.shared-note-sharer');
    if (data.sharerEmail) {
        sharerEl.querySelector('.sharer-email').textContent = data.sharerEmail;
    } else {
        sharerEl.remove();
    }

    // Quyền read-only / editable — chỉ hiện nếu đã xác định
    const permissionEl = card.querySelector('.shared-note-permission');
    if (data.permission === 'read-only') {
        permissionEl.classList.add('read-only');
        permissionEl.querySelector('i').className = 'bi bi-eye';
        permissionEl.querySelector('.permission-label').textContent = 'Read-only';
    } else if (data.permission === 'editable') {
        permissionEl.classList.add('editable');
        permissionEl.querySelector('i').className = 'bi bi-pencil';
        permissionEl.querySelector('.permission-label').textContent = 'Editable';
    } else {
        permissionEl.remove();
    }

    // Nếu KHÔNG có bất kỳ field thông tin nào cả → card rơi về dạng minimal (chỉ icon)
    if (!data.receivedDate && !data.sharerEmail && !data.permission) {
        card.classList.add('is-minimal');
    }

    return card;
}

async function loadSharedNotes() {
    const grid = document.getElementById('sharedGrid');
    grid.innerHTML = '';

    // Dữ liệu giả để test giao diện — sau này thay bằng gọi API thật
    const notes = [
        { title: 'Machine Learning', receivedDate: '19/8/2026', sharerEmail: 'a@gmail.com', permission: 'read-only' },
        { title: 'Project Plan', sharerEmail: 'b@gmail.com', permission: 'editable' },
        { title: 'Random note' }
    ];

    notes.forEach(note => {
        const card = renderSharedNote(note);
        grid.appendChild(card);
    });
}

// Bấm mở shared with me
document.addEventListener('DOMContentLoaded', ()=> {
    const sharedBtn= document.getElementById('shareWithBtn');
    const noteBoardView= document.getElementById('noteBoardView');
    const sharedBoardView= document.getElementById('sharedBoardView');

    if(!sharedBtn || !noteBoardView || !sharedBoardView) return;

    function isSharedViewOpen(){
        return !sharedBoardView.classList.contains('d-none');
    }

    function openSharedView(){
        noteBoardView.classList.add('d-none');
        sharedBoardView.classList.remove('d-none');
        loadShareNotes();
    }

    function closeSharedView(){
        noteBoardView.classList.remove('d-none');
        sharedBoardView.classList.add('d-none');
    }

    sharedBtn.addEventListener('click', (e)=> {
        e.stopPropagation();
        if(isSharedViewOpen()){
            closeSharedView();
        }else{
            openSharedView();
        }
    })

    document.addEventListener('click', (e) =>{
        if(!isSharedViewOpen()) return;

        const clickInsideSharedBoard= sharedBoardView.contains(e.target);
        const clickOnShareBtn= sharedBtn.contains(e.target);

        if(!clickInsideSharedBoard && !clickOnShareBtn){
            closeSharedView();
        }
    })
})

