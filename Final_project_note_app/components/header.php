<!-- Header -->
     <header class="app-header container-fluid d-flex align-items-center justify-content-between px-3 px-md-3 py-2">
        <!-- Menu button for mobile -->
         <button class="icon-btn rounded-3 d-md-none me-2" type="button" id="menuBtn" aria-label="Open sidebar">
            <i class="bi bi-list" id="menuicon"></i>
         </button>
        <!--Logo -->
        <div class="logo-text fs-6 fs-md-5">
            <!-- Desktop/ Tablet -->
            <span class="d-none d-sm-inline">SEEDGROWTHNOTE</span>
            <!-- Mobile -->
            <span class="d-inline d-sm-none">SGN</span>
        </div>
        <!-- Search box -->
        <div class="search-box flex-grow-1 mx-4 mx-md-4 d-none d-sm-block">
            <div class="input-group">
                <span class="input-group-text search-icon-box border-0">
                    <i class="bi bi-search"></i>
                </span>

                <input
                    type="search"
                    id="noteSearchInput"
                    name="notes_search_query"
                    class="form-control search-input border-0 shadow-none"
                    placeholder="Search..."
                    autocomplete="off"
                    value=""
                    aria-label="Search notes"
                >
            </div>
        </div>

        <!-- Icon search for mobile -->
         <button class="icon-btn rounded-circle d-flex d-sm-none align-items-center justify-content-center border-0 me-2">
            <i class="bi bi-search"></i>
         </button>

        <!-- Icon group -->
         <div class="d-flex align-items-center gap-2">
            <!-- Grid or view -->
            <div class="view-toggle-group d-flex p-3">
                <button class="view-toggle-group-btn active rounded-start-3" data-view="grid" aria-label="Grid view">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </button>
                <button class="view-toggle-group-btn rounded-end-3 bg-success-subtle" data-view="list" aria-label="List view">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
            <!-- Light or night -->
            <button class="icon-btn rounded-3" id="themeBtn" aria-label="Mode theme">
                <i class="bi bi-sun-fill" id="themeIcon"></i>
            </button>
         </div>
     </header>