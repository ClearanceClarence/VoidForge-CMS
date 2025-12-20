// Header scroll effect
const header = document.getElementById("header");
window.addEventListener("scroll", () =>
  header.classList.toggle("scrolled", window.scrollY > 50)
);

// Animate chart bars
document.addEventListener("DOMContentLoaded", () => {
  const bars = document.querySelectorAll(".chart-bar");
  const heights = [40, 65, 45, 80, 55, 90, 70];
  bars.forEach((bar, i) => {
    bar.style.height = "0%";
    setTimeout(() => (bar.style.height = heights[i] + "%"), 300 + i * 100);
  });
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) target.scrollIntoView({ behavior: "smooth", block: "start" });
  });
});

// Intersection Observer for animations
const observerOptions = { threshold: 0.1, rootMargin: "0px 0px -50px 0px" };
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = "1";
      entry.target.style.transform = "translateY(0)";
    }
  });
}, observerOptions);

document.querySelectorAll(".feature-card, .bento-card").forEach((card) => {
  card.style.opacity = "0";
  card.style.transform = "translateY(30px)";
  card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
  observer.observe(card);
});
