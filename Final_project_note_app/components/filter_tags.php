
<!-- Filter Tags Panel -->

<section
    class="filter-tags-panel"
    id="filterTagsPanel"
    aria-labelledby="filterTagsTitle"
    hidden
>
    <div class="filter-tags-heading">

        <h2 id="filterTagsTitle">
            Filter tags
        </h2>

        <!-- Close button -->
        <button
            type="button"
            class="filter-tags-close"
            id="closeFilterTagsBtn"
            aria-label="Close Filter tags"
            title="Close Filter tags"
        >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>

    </div>

    <!-- Tag list -->
    <div class="filter-tags-list" id="filterTagsList">

        <button
            class="filter-tag-all is-selected"
            type="button"
            id="filterAllTags"
        >
            All tags
        </button>

        <div
            class="filter-tag-items"
            id="filterTagItems"
        ></div>

    </div>

    <p
        class="filter-tags-message"
        id="filterTagsMessage"
        role="status"
        aria-live="polite"
    ></p>

    <!-- Add tag -->
    <button
        class="filter-tag-add"
        type="button"
        id="addFilterTag"
    >
        + Add tag
    </button>

</section>


<!-- Add / Rename Tag Modal -->

<div
    class="modal fade"
    id="filterTagModal"
    tabindex="-1"
    aria-labelledby="filterTagModalTitle"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">

        <form class="modal-content" id="filterTagForm">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="filterTagModalTitle"
                >
                    Add tag
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>

            <div class="modal-body">

                <label
                    for="filterTagInput"
                    class="form-label"
                >
                    Tag name
                </label>

                <input
                    class="form-control"
                    id="filterTagInput"
                    maxlength="40"
                    required
                    autocomplete="off"
                >

                <div
                    class="invalid-feedback"
                    id="filterTagError"
                ></div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    Save
                </button>

            </div>

        </form>

    </div>
</div>


<!-- Delete Tag Modal -->

<div
    class="modal fade"
    id="deleteFilterTagModal"
    tabindex="-1"
    aria-labelledby="deleteFilterTagModalTitle"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="deleteFilterTagModalTitle"
                >
                    Delete tag?
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <div class="modal-body">
                Deleting this tag will not delete
                the notes associated with it.
            </div>

            <div class="modal-footer">

                <button
                    class="btn btn-secondary"
                    type="button"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-danger"
                    type="button"
                    id="confirmDeleteFilterTag"
                >
                    Delete
                </button>

            </div>

        </div>

    </div>
</div>
