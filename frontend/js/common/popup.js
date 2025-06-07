class ActionPromise {
    constructor() {
        this.resolved = false;
        this.value = null;
    }

    fullfill(value) {
        if (this.resolved) {
            throw new Error("ActionPromise has already been resolved");
        }
        this.value = value;
        this.resolved = true;
    }
}

class Popup {
    // Constructor taking PopupHandler and options and returns action
    constructor(popupHandler, type) {
        const valid_types = ["text", "html"];
        if (!valid_types.includes(type)) {
            throw new Error(`Invalid popup type: ${type}. Valid types are: ${valid_types.join(", ")}`);
        }
        this.type = type;
        this.popupHandler = popupHandler;
        this.id = this._generateRandomId(); // <- Generate a random ID for the popup
    }

    _generateRandomId() {
        return 'popup-' + Math.random().toString(36).substring(2, 9);
    }

    open(content, buttons={}) {

        const actionPromise = new ActionPromise();

        // Buttons are "action_name" => "<button_text>" or "action_name" => ["<button_text>", "<button_type>"]
        // Button types are "primary", "secondary", "danger", "link"

        const wrapper = document.createElement("div");
        wrapper.classList.add("popupjs-wrapper");
        wrapper.id = this.id;
        const contentElem = document.createElement("div");
        contentElem.classList.add("popupjs-content");
        const buttonWrapper = document.createElement("div");
        buttonWrapper.classList.add("popupjs-buttonwrapper");
        
        // For each button add them
        for (let [actionName, button] of Object.entries(buttons)) {
            if (typeof(button) == "string") {
                button = [button, "primary"];
            }

            const buttonElement = document.createElement("button");
            buttonElement.classList.add("popupjs-button", `popupjs-button-${button[1]}`, `popupjs-button-action-${actionName}`);
            buttonElement.textContent = button[0];
            buttonElement.addEventListener("click", () => {
                if (this.actionCallbacks && this.actionCallbacks[actionName]) {
                    this.actionCallbacks[actionName]();
                    actionPromise.fullfill(actionName);
                }
                this.close(actionName);
            });

            buttonWrapper.appendChild(buttonElement);
        }

        if (this.type === "text") {
            const textWrapper = document.createElement("div");
            textWrapper.classList.add("popupjs-content-text-wrapper");
            const textContent = document.createElement("p");
            textContent.classList.add("popupjs-content-text");
            textContent.textContent = content;
            textWrapper.appendChild(textContent);
            contentElem.appendChild(textWrapper);
        } else if (this.type === "html") {
            const htmlWrapper = document.createElement("div");
            htmlWrapper.classList.add("popupjs-content-html-wrapper");
            // If string we set innerHTML, if HTMLElement we append it
            if (typeof content === "string") {
                htmlWrapper.innerHTML = content;
            } else if (content instanceof HTMLElement) {
                htmlWrapper.appendChild(content);
            } else {
                throw new Error("Content must be a string or an HTMLElement for HTML popups");
            }
            contentElem.appendChild(htmlWrapper);
        }

        wrapper.appendChild(contentElem);
        wrapper.appendChild(buttonWrapper);

        this.popupHandler.showPopupDiv(wrapper);

        return actionPromise;
    }

    async openAsync(content, buttons={}, pollRate=100) {
        const actionPromise = this.open(content, buttons);
        
        // Wait until result.resolved becomes true
        await new Promise((resolve) => {
            const interval = setInterval(() => {
            if (actionPromise.resolved) {
                clearInterval(interval);
                resolve();
            }
            }, pollRate);
        });

        return actionPromise.value;
    }

    close(actionName="close") {
        this.popupHandler.hidePopupDiv(this.id);
        return actionName;
    }

    onAction(actionName, callback) {
        // Register a callback for a specific action
        if (typeof actionName !== "string" || typeof callback !== "function") {
            throw new Error("actionName must be a string and callback must be a function");
        }
        this.actionCallbacks = this.actionCallbacks || {};
        this.actionCallbacks[actionName] = callback;
    }
}

class PopupHandler {
    // Constructor taking DOM element where the popup will be rendered in (ex a centered div)
    constructor(popupElement, popupOuterContainer=null) {
        if (!(popupElement instanceof HTMLElement)) {
            throw new Error("popupElement must be a valid DOM element");
        }
        this.popupElement = popupElement;
        this.openedPopups = [];
        this.popupOuterContainer = popupOuterContainer;
        this.hidePopupWrapper();
    }

    showPopupWrapper() {
        this.popupElement.classList.add("popupjs-wrapper-hidden");
        if (this.popupOuterContainer) {
            this.popupOuterContainer.classList.remove("popupjs-outer-container-hidden");
        }
    }

    hidePopupWrapper() {
        this.popupElement.classList.remove("popupjs-wrapper-hidden");
        if (this.popupOuterContainer) {
            this.popupOuterContainer.classList.add("popupjs-outer-container-hidden");
        }
    }

    showPopupDiv(popupDiv) {
        if (!(popupDiv instanceof HTMLElement)) {
            throw new Error("popupDiv must be a valid DOM element");
        }
        this.popupElement.appendChild(popupDiv);
        this.openedPopups.push(popupDiv.id);
        this.showPopupWrapper();
    }

    hidePopupDiv(id) {
        if (this.openedPopups.includes(id)) {
            const popupDiv = document.getElementById(id);
            if (popupDiv) {
                popupDiv.remove();
                this.openedPopups = this.openedPopups.filter(popupId => popupId !== id);
            }
        }
        if (this.openedPopups.length === 0) {
            this.hidePopupWrapper();
        }
    }

    hideAllPopups() {
        this.openedPopups.forEach(id => {
            const popupDiv = document.getElementById(id);
            if (popupDiv) {
                popupDiv.remove();
            }
        });
        this.openedPopups = [];
        this.hidePopupWrapper();
    }
}