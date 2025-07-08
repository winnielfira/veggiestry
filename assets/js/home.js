let currentSlide = 0;
let slideInterval;
let categorySlidePosition = 0;
let totalSlides = 2;

function showSlide(n) {
  const slides = document.querySelectorAll(".banner-slide");
  const dots = document.querySelectorAll(".dot");

  if (n >= slides.length) currentSlide = 0;
  if (n < 0) currentSlide = slides.length - 1;

  slides.forEach((slide) => slide.classList.remove("active"));
  dots.forEach((dot) => dot.classList.remove("active"));

  if (slides[currentSlide] && dots[currentSlide]) {
    slides[currentSlide].classList.add("active");
    dots[currentSlide].classList.add("active");
  }
}

function nextSlide() {
  currentSlide++;
  showSlide(currentSlide);
}

function prevSlide() {
  currentSlide--;
  showSlide(currentSlide);
}

function goToSlide(n) {
  currentSlide = n - 1;
  showSlide(currentSlide);
}

window.currentSlide = goToSlide;

function startAutoSlide() {
  if (slideInterval) {
    clearInterval(slideInterval);
  }
  slideInterval = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
  if (slideInterval) {
    clearInterval(slideInterval);
    slideInterval = null;
  }
}

function slideCategories(direction) {
  const container = document.querySelector(".categories-container");
  if (!container) return;

  const cards = container.querySelectorAll(".category-card");
  if (cards.length === 0) return;

  const cardWidth = 225;
  const containerWidth = container.parentElement.offsetWidth - 120;
  const visibleCards = Math.floor(containerWidth / cardWidth);
  const maxSlideIndex = Math.max(0, cards.length - visibleCards);

  if (direction > 0) {
    categorySlidePosition = Math.min(categorySlidePosition + 1, maxSlideIndex);
  } else {
    categorySlidePosition = Math.max(categorySlidePosition - 1, 0);
  }

  const translateX = -(categorySlidePosition * cardWidth);
  container.style.transform = `translateX(${translateX}px)`;

  updateCategoryButtons();

  console.log(
    `Category slide: position ${categorySlidePosition}, max ${maxSlideIndex}`
  );
}

function updateCategoryButtons() {
  const container = document.querySelector(".categories-container");
  const prevBtn = document.querySelector(".slider-btn.prev");
  const nextBtn = document.querySelector(".slider-btn.next");

  if (!container || !prevBtn || !nextBtn) return;

  const cards = container.querySelectorAll(".category-card");
  const cardWidth = 225;
  const containerWidth = container.parentElement.offsetWidth - 120;
  const visibleCards = Math.floor(containerWidth / cardWidth);
  const maxSlideIndex = Math.max(0, cards.length - visibleCards);

  if (categorySlidePosition === 0) {
    prevBtn.style.opacity = "0.5";
    prevBtn.style.pointerEvents = "none";
  } else {
    prevBtn.style.opacity = "1";
    prevBtn.style.pointerEvents = "auto";
  }

  if (categorySlidePosition >= maxSlideIndex) {
    nextBtn.style.opacity = "0.5";
    nextBtn.style.pointerEvents = "none";
  } else {
    nextBtn.style.opacity = "1";
    nextBtn.style.pointerEvents = "auto";
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("Home page loaded, initializing banner auto-slide...");

  const slides = document.querySelectorAll(".banner-slide");
  const dots = document.querySelectorAll(".dot");

  if (slides.length > 0) {
    console.log(`Found ${slides.length} banner slides`);

    currentSlide = 0;
    showSlide(currentSlide);

    if (slides.length > 1) {
      startAutoSlide();
      console.log("Auto-slide started (5 seconds interval)");
    }

    const bannerSection = document.querySelector(".banner-section");
    if (bannerSection) {
      bannerSection.addEventListener("mouseenter", () => {
        console.log("Auto-slide paused (mouse enter)");
        stopAutoSlide();
      });

      bannerSection.addEventListener("mouseleave", () => {
        console.log("Auto-slide resumed (mouse leave)");
        startAutoSlide();
      });
    }

    dots.forEach((dot, index) => {
      dot.addEventListener("click", () => {
        console.log(`Dot ${index + 1} clicked`);
        goToSlide(index + 1);
        stopAutoSlide();
        setTimeout(startAutoSlide, 1000);
      });
    });

    let startX = 0;
    let endX = 0;

    bannerSection.addEventListener("touchstart", (e) => {
      startX = e.touches[0].clientX;
    });

    bannerSection.addEventListener("touchend", (e) => {
      endX = e.changedTouches[0].clientX;
      handleSwipe();
    });

    function handleSwipe() {
      const swipeThreshold = 50;
      const diff = startX - endX;

      if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
          nextSlide();
        } else {
          prevSlide();
        }
        stopAutoSlide();
        setTimeout(startAutoSlide, 1000);
      }
    }
  } else {
    console.log("No banner slides found");
  }

  const categoriesContainer = document.querySelector(".categories-container");
  if (categoriesContainer) {
    categorySlidePosition = 0;
    categoriesContainer.style.transform = "translateX(0px)";

    setTimeout(() => {
      updateCategoryButtons();
    }, 100);

    let categoryStartX = 0;
    let categoryEndX = 0;

    categoriesContainer.addEventListener("touchstart", (e) => {
      categoryStartX = e.touches[0].clientX;
    });

    categoriesContainer.addEventListener("touchend", (e) => {
      categoryEndX = e.changedTouches[0].clientX;
      handleCategorySwipe();
    });

    function handleCategorySwipe() {
      const swipeThreshold = 50;
      const diff = categoryStartX - categoryEndX;

      if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
          slideCategories(1);
        } else {
          slideCategories(-1);
        }
      }
    }

    window.addEventListener("resize", () => {
      categorySlidePosition = 0;
      categoriesContainer.style.transform = "translateX(0px)";
      setTimeout(() => {
        updateCategoryButtons();
      }, 100);
    });
  }
});

document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

const observerOptions = {
  threshold: 0.1,
  rootMargin: "0px 0px -50px 0px",
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = "1";
      entry.target.style.transform = "translateY(0)";
    }
  });
}, observerOptions);

document.addEventListener("DOMContentLoaded", () => {
  const animateElements = document.querySelectorAll(
    ".product-card, .feature-card, .category-card"
  );

  animateElements.forEach((el) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    el.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(el);
  });
});

window.addEventListener("beforeunload", () => {
  stopAutoSlide();
});

document.addEventListener("visibilitychange", () => {
  if (document.hidden) {
    stopAutoSlide();
  } else {
    const slides = document.querySelectorAll(".banner-slide");
    if (slides.length > 1) {
      startAutoSlide();
    }
  }
});

window.slideCategories = slideCategories;

window.debugCategorySlider = function () {
  console.log("🔍 Category Slider Debug Info:");
  console.log("Current position:", categorySlidePosition);
  const container = document.querySelector(".categories-container");
  const cards = container ? container.querySelectorAll(".category-card") : [];
  console.log("Total categories:", cards.length);
  const cardWidth = 225;
  const containerWidth = container
    ? container.parentElement.offsetWidth - 120
    : 0;
  const visibleCards = Math.floor(containerWidth / cardWidth);
  console.log("Visible categories:", visibleCards);
  console.log("Max slide index:", Math.max(0, cards.length - visibleCards));
};
window.debugSlider = function () {
  console.log("🔍 Slider Debug Info:");
  console.log("Current slide:", currentSlide);
  console.log(
    "Total slides:",
    document.querySelectorAll(".banner-slide").length
  );
  console.log("Interval active:", slideInterval !== null);
  console.log("Interval ID:", slideInterval);

  async function loadProducts() {
    console.log("Loading products for homepage...");

    try {
      const response = await fetch("api/get-products.php?action=getAll");
      const products = await response.json();

      console.log("Products loaded:", products);

      if (Array.isArray(products)) {
        displayProducts(products);
      } else {
        console.error("Invalid products data:", products);
      }
    } catch (error) {
      console.error("Error loading products:", error);
    }
  }

  function displayProducts(products) {
    const container = document.querySelector(".products-grid");
    if (!container) {
      console.log("Products grid container not found");
      return;
    }

    console.log("Displaying", products.length, "products");

    products.forEach((product) => {
      const soldCount = product.total_sold || product.sold || 0;
      const rating = parseFloat(product.avg_rating) || 0;

      console.log(
        `Product: ${product.name}, Rating: ${rating}, Sold: ${soldCount}`
      );

      updateProductCard(product, rating, soldCount);
    });
  }

  function updateProductCard(product, rating, soldCount) {
    const productCards = document.querySelectorAll(".product-card");

    productCards.forEach((card) => {
      const productName = card.querySelector("h3")?.textContent;
      if (productName && productName.includes(product.name)) {
        const ratingSpan = card.querySelector(".product-rating span");
        if (ratingSpan) {
          ratingSpan.textContent = `${rating.toFixed(1)} (${soldCount})`;
          console.log(
            `Updated rating for ${product.name}: ${rating.toFixed(
              1
            )} (${soldCount})`
          );
        }

        const stars = card.querySelectorAll(".product-rating .fas.fa-star");
        stars.forEach((star, index) => {
          if (index < Math.floor(rating)) {
            star.classList.add("filled");
          } else {
            star.classList.remove("filled");
          }
        });
      }
    });
  }

  function generateStarRating(rating) {
    let stars = "";
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;

    for (let i = 1; i <= 5; i++) {
      if (i <= fullStars) {
        stars += '<i class="fas fa-star filled"></i>';
      } else if (i === fullStars + 1 && hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt filled"></i>';
      } else {
        stars += '<i class="fas fa-star"></i>';
      }
    }
    return stars;
  }

  document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
      loadProducts();
    }, 1000);
  });

  console.log("Product display functions loaded");
};
