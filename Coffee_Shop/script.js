fetch("navigation.php")
    .then((response) => {
        if (!response.ok) {
            throw new Error(`Navigation failed to load: ${response.status}`);
        }

        return response.text();
    })
    .then((navigation) => {
        document.querySelector("#site-navigation").innerHTML = navigation;
    })
    .catch((error) => console.error(error));

const pageContent = document.querySelector("#page-content");
const prices = {};

async function loadPageContent() {
    if (!pageContent) return;

    const response = await fetch("menu-content.php");
    if (!response.ok) {
        throw new Error(`Page content failed to load: ${response.status}`);
    }

    const source = await response.text();
    const sourceDocument = new DOMParser().parseFromString(source, "text/html");
    const pageType = pageContent.dataset.page;
    if (pageType !== "home") {
        const productResponse = await fetch("product-sections.php");
        if (!productResponse.ok) {
            throw new Error(`Product catalog failed to load: ${productResponse.status}`);
        }
        const productSource = await productResponse.text();
        const productDocument = new DOMParser().parseFromString(productSource, "text/html");
        ["menu-gallery", "menu-prices"].forEach((sectionId) => {
            const section = productDocument.querySelector(`#${sectionId}`);
            if (section) sourceDocument.querySelector(`#${sectionId}`)?.replaceWith(section);
        });
    }

    sourceDocument.querySelectorAll(".menu-row").forEach((row) => {
        const name = row.children[0]?.textContent.trim();
        const amount = row.children[1]?.textContent.replace(/[^0-9.]/g, "");
        if (name && amount) prices[name] = Number(amount);
    });
    const sectionIds = pageType === "home"
        ? ["home", "about", "contact"]
        : pageType === "gallery" || pageType === "buy"
            ? ["menu-gallery", "contact"]
            : ["menu-prices", "contact"];

    sectionIds.forEach((sectionId) => {
        const section = sourceDocument.querySelector(`#${sectionId}`);
        if (section) pageContent.append(section.cloneNode(true));
    });

    if (pageType === "gallery" || pageType === "buy") {
        const recipeModal = sourceDocument.querySelector("#recipe-modal");
        if (recipeModal) document.body.append(recipeModal.cloneNode(true));
    }

    if (pageType === "buy") {
        pageContent.querySelector(".menu-gallery-title h3").textContent = "Menus";
        ["cart-button", "cart-modal"].forEach((elementId) => {
            const element = sourceDocument.querySelector(`#${elementId}`);
            if (element) document.body.append(element.cloneNode(true));
        });
    }
}

async function initializePage() {
    await loadPageContent();

const recipeModal = document.querySelector("#recipe-modal");
if (!recipeModal) return;
const recipeTitle = document.querySelector("#recipe-title");
const recipeIngredients = document.querySelector("#recipe-ingredients");
const recipePreparation = document.querySelector("#recipe-preparation");
const recipeCloseButton = document.querySelector(".recipe-close");

function closeRecipe() {
    recipeModal.hidden = true;
    document.body.style.overflow = "";
}

document.querySelectorAll(".recipe-button").forEach((button) => {
    button.addEventListener("click", () => {
        const [title, ingredients, preparation] = button.dataset.recipeTitle
            ? [button.dataset.recipeTitle, button.dataset.recipeIngredients, button.dataset.recipePreparation]
            : button.dataset.recipe.split("|");

        recipeTitle.textContent = title;
        recipeIngredients.textContent = ingredients;
        recipePreparation.textContent = preparation;
        recipeModal.hidden = false;
        document.body.style.overflow = "hidden";
        recipeCloseButton.focus();
    });
});

recipeCloseButton.addEventListener("click", closeRecipe);

recipeModal.addEventListener("click", (event) => {
    if (event.target === recipeModal) {
        closeRecipe();
    }
});

let cart = [];
const cartModal = document.querySelector("#cart-modal");
const cartItems = document.querySelector("#cart-items");
const cartCount = document.querySelector("#cart-count");
const cartTotal = document.querySelector("#cart-total");
const checkoutMessage = document.querySelector("#checkout-message");

function formatPrice(amount) {
    return `₱${amount.toFixed(2)}`;
}

function normalizeQuantity(value) {
    return Math.max(1, Math.min(20, Number(value) || 1));
}

function renderCart() {
    const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    cartCount.textContent = itemCount;
    cartTotal.textContent = formatPrice(total);
    cartItems.innerHTML = cart.length === 0
        ? "<p class=\"empty-cart\">Your cart is empty.</p>"
        : cart.map((item, index) => `
            <div class="cart-item">
                <div>
                    <strong>${item.name}</strong>
                    <span>${formatPrice(item.price)} each</span>
                </div>
                <label>Qty
                    <input class="cart-quantity" type="number" min="1" max="20" value="${item.quantity}" data-index="${index}">
                </label>
                <button class="remove-item" type="button" data-index="${index}">Remove</button>
            </div>
        `).join("");
}

async function updateServerCart(action, name = "", quantity) {
    const response = await fetch("cart-action.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": pageContent.dataset.csrfToken,
        },
        body: JSON.stringify({ action, name, quantity }),
    });
    const result = await response.json();
    if (response.status === 401) {
        window.location.href = "login.php?return=buy-now.php";
        throw new Error("Please sign in to use your cart.");
    }
    if (!response.ok) {
        throw new Error(result.error || "We could not update your cart.");
    }
    cart = result.items.map((item) => ({
        id: Number(item.id),
        name: item.name,
        price: Number(item.price),
        quantity: Number(item.quantity),
    }));
    renderCart();
}

async function loadServerCart() {
    const response = await fetch("cart-action.php");
    const result = await response.json();
    if (!response.ok) {
        throw new Error(result.error || "We could not load your cart.");
    }
    cart = result.items.map((item) => ({
        id: Number(item.id),
        name: item.name,
        price: Number(item.price),
        quantity: Number(item.quantity),
    }));
    renderCart();
}

function showCartError(error) {
    console.error("Cart operation failed.", error);
    if (cartModal.hidden) openCart();
    checkoutMessage.textContent = error.message || "We could not update your cart.";
}

function openCart() {
    renderCart();
    checkoutMessage.textContent = "";
    cartModal.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeCart() {
    cartModal.hidden = true;
    document.body.style.overflow = "";
}

async function addToCart(name, quantity) {
    quantity = normalizeQuantity(quantity);
    await updateServerCart("add", name, quantity);
    checkoutMessage.textContent = "";
}

if (pageContent?.dataset.page === "buy") {
await loadServerCart().catch(showCartError);
document.querySelectorAll(".menu-item").forEach((item) => {
    const name = item.querySelector("h4").textContent.trim();
    const price = prices[name];
    if (!price) return;

    const controls = document.createElement("div");
    controls.className = "purchase-controls";
    controls.innerHTML = `
        <strong class="product-price">${formatPrice(price)}</strong>
        <label class="quantity-label">Qty
            <input class="product-quantity" type="number" min="1" max="20" value="1" aria-label="Quantity for ${name}">
        </label>
        <div class="purchase-actions">
            <button class="add-cart-button" type="button">Add to Cart</button>
            <button class="buy-button" type="button">Buy Now</button>
        </div>
    `;
    item.append(controls);

    const quantityInput = controls.querySelector(".product-quantity");
    controls.querySelector(".add-cart-button").addEventListener("click", async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        try {
            await addToCart(name, Number(quantityInput.value));
            button.textContent = "Added";
            setTimeout(() => { button.textContent = "Add to Cart"; }, 1200);
        } catch (error) {
            showCartError(error);
            button.textContent = "Try Again";
        } finally {
            button.disabled = false;
        }
    });
    controls.querySelector(".buy-button").addEventListener("click", async () => {
        try {
            await updateServerCart("clear");
            await addToCart(name, Number(quantityInput.value));
            openCart();
        } catch (error) {
            showCartError(error);
        }
    });
});
document.querySelector("#cart-button").addEventListener("click", openCart);
document.querySelector("#cart-close").addEventListener("click", closeCart);
cartModal.addEventListener("click", async (event) => {
    if (event.target === cartModal) closeCart();
    if (event.target.matches(".remove-item")) {
        const item = cart[Number(event.target.dataset.index)];
        try {
            await updateServerCart("remove", item.name);
        } catch (error) {
            showCartError(error);
        }
    }
});

cartModal.addEventListener("change", async (event) => {
    if (!event.target.matches(".cart-quantity")) return;
    const item = cart[Number(event.target.dataset.index)];
    try {
        await updateServerCart("quantity", item.name, normalizeQuantity(event.target.value));
    } catch (error) {
        showCartError(error);
    }
});

document.querySelector("#checkout-button").addEventListener("click", async () => {
    if (cart.length === 0) return;
    const checkoutButton = document.querySelector("#checkout-button");
    checkoutButton.disabled = true;
    checkoutMessage.textContent = "Placing your order...";
    try {
        const response = await fetch("place-order.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": pageContent.dataset.csrfToken,
            },
            body: JSON.stringify({}),
        });
        const result = await response.json();
        if (response.status === 401) {
            window.location.href = "login.php?return=buy-now.php";
            return;
        }
        if (!response.ok) {
            throw new Error(result.error || "We could not place your order.");
        }

        cart = [];
        renderCart();
        window.location.href = `receipt.php?order_id=${encodeURIComponent(result.order_id)}`;
        return;
    } catch (error) {
        checkoutMessage.textContent = error.message || "We could not place your order. Please try again.";
        console.error("Order submission failed.", error);
    } finally {
        checkoutButton.disabled = false;
    }
});

}

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !recipeModal.hidden) {
        closeRecipe();
    }
    if (event.key === "Escape" && cartModal && !cartModal.hidden) {
        closeCart();
    }
});

}

initializePage().catch((error) => console.error(error));