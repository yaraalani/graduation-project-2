document.addEventListener('DOMContentLoaded', function() {
  // Simple animation trigger on scroll
  const animateElements = document.querySelectorAll('.animate__animated');
  
  const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
          if (entry.isIntersecting) {
              const animation = entry.target.getAttribute('data-animation');
              entry.target.classList.add(animation);
          }
      });
  }, {
      threshold: 0.1
  });
  
  animateElements.forEach(element => {
      observer.observe(element);
  });
  
  // Floating shapes animation
  const shapes = document.querySelectorAll('.shape');
  shapes.forEach((shape, index) => {
      // Random initial position and animation
      const randomX = Math.random() * 20 - 10;
      const randomY = Math.random() * 20 - 10;
      const duration = 15 + Math.random() * 10;
      
      shape.style.transform = `translate(${randomX}px, ${randomY}px)`;
      shape.style.animation = `float ${duration}s ease-in-out infinite alternate`;
      
      // Add animation style dynamically
      const style = document.createElement('style');
      style.textContent = `
          @keyframes float {
              0% {
                  transform: translate(${randomX}px, ${randomY}px);
              }
              50% {
                  transform: translate(${-randomX}px, ${-randomY}px);
              }
              100% {
                  transform: translate(${randomX}px, ${randomY}px);
              }
          }
      `;
      document.head.appendChild(style);
  });
});
document.addEventListener('DOMContentLoaded', function() {
    // Floating icons animation
    const icons = document.querySelectorAll('.floating-icon');
    icons.forEach((icon, index) => {
        const duration = 15 + Math.random() * 10;
        const delay = Math.random() * 5;
        
        icon.style.animation = `float ${duration}s ease-in-out ${delay}s infinite alternate`;
        
        // Create unique animation for each icon
        const keyframes = `
            @keyframes float {
                0% {
                    transform: translate(0, 0) rotate(0deg);
                }
                50% {
                    transform: translate(${Math.random() * 30 - 15}px, ${Math.random() * 30 - 15}px) rotate(${Math.random() * 20 - 10}deg);
                }
                100% {
                    transform: translate(0, 0) rotate(0deg);
                }
            }
        `;
        
        const style = document.createElement('style');
        style.innerHTML = keyframes;
        document.head.appendChild(style);
    });
});