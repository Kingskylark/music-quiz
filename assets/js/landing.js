/**
 * Landing Page Animations & Interactions
 */

// Simple AOS-like animation on scroll
document.addEventListener('DOMContentLoaded', function() {
    // Animate elements on page load
    const animatedElements = document.querySelectorAll('[data-aos]');
    
    setTimeout(() => {
        animatedElements.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('aos-animate');
            }, index * 100); // Stagger animation
        });
    }, 200);
    
    // Add pulse effect to Start Quiz button
    const startBtn = document.querySelector('[data-bs-target="#rulesModal"]');
    if (startBtn) {
        setInterval(() => {
            startBtn.classList.add('pulse');
            setTimeout(() => {
                startBtn.classList.remove('pulse');
            }, 1000);
        }, 3000);
    }
    
    // Particle effect on button hover (optional)
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.02)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

// Add pulse animation class
const style = document.createElement('style');
style.textContent = `
    .pulse {
        animation: pulse-effect 1s ease;
    }
    
    @keyframes pulse-effect {
        0%, 100% {
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.5);
        }
        50% {
            box-shadow: 0 10px 50px rgba(255, 193, 7, 0.8);
            transform: translateY(-5px) scale(1.05);
        }
    }
`;
document.head.appendChild(style);