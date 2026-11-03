
/*
 * SeedGrowthNote - Note Context Menu
 * Works with #noteGrid and .note-card
 */

(() => {
    "use strict";

    const actions = [
        { name: "pin", label: "Pin", icon: "bi-pin-angle" },
        { name: "share", label: "Share", icon: "bi-share" },
        { name: "lock", label: "Lock", icon: "bi-lock" },
        { name: "tag", label: "Tag", icon: "bi-tags" },
        { name: "delete", label: "Delete", icon: "bi-trash" }
    ];

    function closeAllMenus() {
        document.querySelectorAll(
            "#noteGrid .note-context-menu"
        ).forEach(menu => {
            menu.hidden = true;
        });

        document.querySelectorAll(
            "#noteGrid .note-menu-trigger"
        ).forEach(button => {
            button.setAttribute("aria-expanded", "false");
        });
    }

    function createMenu() {
        const menu = document.createElement("div");
        menu.className = "note-context-menu";
        menu.hidden = true;

        actions.forEach(action => {
            const button = document.createElement("button");
            button.type = "button";
            button.dataset.action = action.name;

            const icon = document.createElement("i");
            icon.className = `bi ${action.icon}`;

            const label = document.createElement("span");
            label.textContent = action.label;

            button.append(icon, label);
            menu.appendChild(button);
        });

        return menu;
    }

    function enhanceNoteCards() {
        const grid = document.getElementById("noteGrid");

        if (!grid) return;

        grid.querySelectorAll(".note-card").forEach(card => {
            if (card.querySelector(".note-menu-trigger")) {
                return;
            }

            const link = card.querySelector(".note-card-link");
            const seed = card.querySelector(".note-card-icon");

            if (!link || !seed) return;

            const trigger = document.createElement("button");

            trigger.type = "button";
            trigger.className = "note-menu-trigger";
            trigger.textContent = "⋮";

            trigger.setAttribute(
                "aria-label",
                "Note options"
            );

            trigger.setAttribute(
                "aria-expanded",
                "false"
            );

            trigger.setAttribute(
                "aria-haspopup",
                "true"
            );

            // Place button outside note link
            link.insertAdjacentElement("afterend", trigger);

            // Add dropdown
            card.appendChild(createMenu());
        });
    }

    function handleAction(card, action) {
        const noteId = card.dataset.noteId;

        if (!noteId) {
            console.warn("Missing note ID.");
            return;
        }

        document.dispatchEvent(
            new CustomEvent("seedgrowth:note-action", {
                detail: {
                    noteId,
                    action
                }
            })
        );

        console.log("Note menu:", action, noteId);
    }

    function handleClick(event) {
        const grid = document.getElementById("noteGrid");

        if (!grid) return;

        const trigger = event.target.closest(
            ".note-menu-trigger"
        );

        if (trigger && grid.contains(trigger)) {
            event.preventDefault();
            event.stopPropagation();

            const card = trigger.closest(".note-card");
            const menu = card?.querySelector(
                ".note-context-menu"
            );

            if (!menu) return;

            const shouldOpen = menu.hidden;

            closeAllMenus();

            menu.hidden = !shouldOpen;

            trigger.setAttribute(
                "aria-expanded",
                String(shouldOpen)
            );

            return;
        }

        const actionButton = event.target.closest(
            ".note-context-menu [data-action]"
        );

        if (actionButton && grid.contains(actionButton)) {
            event.preventDefault();
            event.stopPropagation();

            const card = actionButton.closest(".note-card");

            if (card) {
                handleAction(
                    card,
                    actionButton.dataset.action
                );
            }

            closeAllMenus();
            return;
        }

        if (!event.target.closest(".note-context-menu")) {
            closeAllMenus();
        }
    }

    function init() {
        const grid = document.getElementById("noteGrid");

        if (!grid) return;

        enhanceNoteCards();

        // Observe notes loaded asynchronously from database
        const observer = new MutationObserver(() => {
            enhanceNoteCards();
        });

        observer.observe(grid, {
            childList: true
        });

        // Capture click before note editor handlers
        document.addEventListener(
            "click",
            handleClick,
            true
        );

        document.addEventListener("keydown", event => {
            if (event.key === "Escape") {
                closeAllMenus();
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
