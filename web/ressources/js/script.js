// Fetch image data from PHP API
let imageData = [];
let isLoading = true;

// Fetch images from PHP API
async function fetchImages() {
    try {
        const response = await fetch('api/get_images.php');
        const result = await response.json();
        
        if (result.success) {
            imageData = result.data;
        } else {
            console.error('Error fetching images:', result.error);
            imageData = [];
        }
    } catch (error) {
        console.error('Error fetching images:', error);
        imageData = [];
    }
}

// Render the gallery
function renderGallery() {
    const gallery = document.getElementById('gallery');
    let html = '';
    
    if (isLoading) {
        gallery.innerHTML = '<div class="no-images">Loading images...</div>';
        return;
    }
    
    let totalImages = 0;
    imageData.forEach(folder => {
        totalImages += folder.images.length;
    });
    
    if (totalImages === 0) {
        gallery.innerHTML = '<div class="no-images">No images found.</div>';
        return;
    }
    
    imageData.forEach(folder => {
        html += `<div class="folder-section">`;
        html += `<div class="folder-title">${folder.folder} (${folder.images.length} images)</div>`;
        html += `<div class="image-grid">`;
        
        folder.images.forEach(imageName => {
            const imagePath = `storage/screenshot/${folder.folder}/${imageName}`;
            html += `
                <div class="image-item">
                    <img src="${imagePath}" alt="${imageName}" loading="lazy" onerror="this.style.display='none'">
                    <div class="image-name">${imageName}</div>
                </div>
            `;
        });
        
        html += `</div>`;
        html += `</div>`;
    });
    
    gallery.innerHTML = html;
}

// Lightbox functionality
function openLightbox(imageSrc, imageName) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    
    lightboxImg.src = imageSrc;
    lightboxCaption.textContent = imageName;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    lightbox.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Add click event to images after gallery is rendered
document.addEventListener('click', function(e) {
    const imageItem = e.target.closest('.image-item');
    
    if (imageItem) {
        const img = imageItem.querySelector('img');
        if (img && e.target === img) {
            const imageSrc = img.src;
            const imageName = img.alt;
            openLightbox(imageSrc, imageName);
        }
    }
});

// Close lightbox on click
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('lightbox-close')) {
        closeLightbox();
    }
});

// Close lightbox on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});

// Initialize gallery on page load
document.addEventListener('DOMContentLoaded', async function() {
    isLoading = true;
    renderGallery();
    
    await fetchImages();
    
    isLoading = false;
    renderGallery();
});
