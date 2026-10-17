<!-- Share note -->
<div class="modal fade share-note-modal" id="shareNoteModal" tabindex="-1"
     aria-labelledby="shareNoteTitle" aria-hidden="true" style="display: block;">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content share-note-content">

            <!-- Header -->
            <div class="share-note-header">
                <div class="share-note-title">
                    <div class="share-note-logo"><i class="bi bi-leaf-fill"></i></div>
                    <h5 id="shareNoteTitle">Share Note</h5>
                </div>
                <button type="button" class="share-note-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="share-note-body">
                <section class="share-section">
                    <h6 class="share-section-title">
                        <i class="bi bi-people"></i>
                        <span>People with access</span>
                    </h6>

                    <div class="share-add-row">
                        <input type="email" id="shareUserInput" class="form-control share-user-input"
                               placeholder="Enter email..." autocomplete="off" aria-label="Recipient email">
                        <select id="shareDefaultPermission" class="form-select share-permission-select"
                                aria-label="Permission">
                            <option value="read">Can view</option>
                            <option value="edit">Can edit</option>
                        </select>
                        <button type="button" class="btn share-add-btn" id="shareAddBtn">Add</button>
                    </div>

                    <div class="share-error" id="shareError" role="alert"></div>

                    <div class="share-people-list" id="sharePeopleList"></div>
                    <p class="share-empty" id="shareEmpty">No one added yet.</p>
                </section>
            </div>

            <div class="share-note-footer">
                <button type="button" class="btn share-confirm-btn" id="shareConfirmBtn" disabled>
                    <i class="bi bi-send"></i>
                    <span>Share</span>
                </button>
            </div>

        </div>
    </div>
</div>
