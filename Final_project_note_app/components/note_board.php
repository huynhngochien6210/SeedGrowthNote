<!-- Note board -->
<div class="note-board">
    <div class="note-board-grid d-flex flex-wrap" id="noteGrid">
        <!-- Create note -->
         <div class="new-note-card d-flex align-items-center gap-3 align-items-center" type="button">           
            <span class="new-note-circle rounded-circle d-flex align-items-center justify-content-center" id="newNoteTrigger">
                <!-- icon dau cong -->
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier"> 
                        <rect width="24" height="24"></rect> 
                        <path d="M12 6V18" stroke="#000000" stroke-linecap="round" stroke-linejoin="round"></path> 
                        <path d="M6 12H18" stroke="#000000" stroke-linecap="round" stroke-linejoin="round"></path> 
                    </g>
                </svg>
                <!-- seed badge -->
                <span class="note-icon-badge">
                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.004 512.004" xml:space="preserve" fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier"> 
                            <g> 
                                <path style="fill:#f0dd8a;" d="M370.114,157.811C351.523,65.174,307.456,0,256,0c-51.447,0-95.514,65.174-114.114,157.811 c41.057,46.036,72.854,122.827,92.328,199.477C251.065,406.528,256,461.356,256,512c0-50.644,4.935-105.472,21.786-154.712 C297.269,280.638,329.057,203.847,370.114,157.811"></path> 
                                <path style="fill:#AF6D2D;" d="M256.002,512.004c0-138.664-77.241-397.241-194.207-397.241 C-19.966,351.668,96.373,512.004,256.002,512.004"></path> 
                                <path style="fill:#864D18;" d="M185.347,471.952c-1.121,0-2.26-0.221-3.363-0.671c-31.603-13.065-58.033-34.278-78.539-63.047 c-41.393-58.068-52.515-141.047-31.329-233.657c1.086-4.758,5.826-7.733,10.575-6.63c4.749,1.086,7.724,5.817,6.638,10.567 c-20.047,87.623-9.931,165.57,28.495,219.471c18.556,26.033,42.408,45.197,70.894,56.973c4.511,1.863,6.656,7.027,4.793,11.529 C192.1,469.895,188.816,471.952,185.347,471.952"></path> 
                                <path style="fill:#AF6D2D;" d="M256.002,512.004c0-138.664,77.241-397.241,194.207-397.241 C531.97,351.668,415.631,512.004,256.002,512.004"></path> 
                                <path style="fill:#B49377;" d="M184.403,141.249c-0.83,0-1.66-0.124-2.498-0.362c-4.67-1.377-7.353-6.285-5.976-10.964 c11.714-39.812,28.637-71.318,47.634-88.691c3.593-3.302,9.181-3.046,12.473,0.556c3.293,3.593,3.037,9.181-0.556,12.473 c-16.419,15.007-31.947,44.403-42.611,80.649C191.739,138.751,188.216,141.249,184.403,141.249"></path> 
                            </g> 
                        </g>
                    </svg>
                </span>
            </span>
        </div>
        <!-- Input file -->
         <input type="file" id="hiddenFileInput" hidden>
    </div>
</div>

<!-- Note template -->
 <template id="noteTemplate">
    <!-- Notes -->
    <div class="note-card">
        <a href="#" class="note-card-link">
            <span class="note-card-icon is-clickable" style="--seed-body: #0eaf4c;">
                <!-- seed icon -->
                <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.004 512.004" xml:space="preserve" fill="#000000">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier"> 
                        <g> 
                            <path style="fill:#fbefbc;" d="M370.114,157.811C351.523,65.174,307.456,0,256,0c-51.447,0-95.514,65.174-114.114,157.811 c41.057,46.036,72.854,122.827,92.328,199.477C251.065,406.528,256,461.356,256,512c0-50.644,4.935-105.472,21.786-154.712 C297.269,280.638,329.057,203.847,370.114,157.811"></path> 
                            <path class="body" style="fill: var(--seed-body);" d="M256.002,512.004c0-138.664-77.241-397.241-194.207-397.241 C-19.966,351.668,96.373,512.004,256.002,512.004"></path> 
                            <path style="fill:#504e4e;" d="M185.347,471.952c-1.121,0-2.26-0.221-3.363-0.671c-31.603-13.065-58.033-34.278-78.539-63.047 c-41.393-58.068-52.515-141.047-31.329-233.657c1.086-4.758,5.826-7.733,10.575-6.63c4.749,1.086,7.724,5.817,6.638,10.567 c-20.047,87.623-9.931,165.57,28.495,219.471c18.556,26.033,42.408,45.197,70.894,56.973c4.511,1.863,6.656,7.027,4.793,11.529 C192.1,469.895,188.816,471.952,185.347,471.952"></path> 
                            <path style="fill: var(--seed-body);" d="M256.002,512.004c0-138.664,77.241-397.241,194.207-397.241 C531.97,351.668,415.631,512.004,256.002,512.004"></path> 
                            <path style="fill:#504e4e;" d="M184.403,141.249c-0.83,0-1.66-0.124-2.498-0.362c-4.67-1.377-7.353-6.285-5.976-10.964 c11.714-39.812,28.637-71.318,47.634-88.691c3.593-3.302,9.181-3.046,12.473,0.556c3.293,3.593,3.037,9.181-0.556,12.473 c-16.419,15.007-31.947,44.403-42.611,80.649C191.739,138.751,188.216,141.249,184.403,141.249"></path> 
                        </g> 
                    </g>
                </svg>
            </span>
            <span class="note-card-title">Untitled</span>
            <span class="note-card-date"></span>
        </a>
    </div>
 </template>

 <!-- Khung menu -->
<div class="note-dropdown-menu" id="newNoteMenu" hidden>
    <!-- create note -->
    <button type="button" class="note-dropdown-item" id=btnCreateNote>
        <i class="bi bi-file-earmark-plus-fill"></i>
            <span>Create note</span>
    </button>
    <!-- upload file -->
    <button type="button" class="note-dropdown-item" id="btnUploadFile">
        <i class="bi bi-folder-fill"></i>
        <span>Upload file</span>
    </button>
</div>

  <!-- Popover chọn màu, dùng chung cho cả board -->
<div class="color-popover" id="colorPopover" hidden>
    <button type="button" class="color-swatch" data-color="#8FCB9B" style="background:#8FCB9B;"></button>
    <button type="button" class="color-swatch" data-color="#D6B56E" style="background:#D6B56E;"></button>
    <button type="button" class="color-swatch" data-color="#A67C52"  style="background:#A67C52;"></button>
</div>