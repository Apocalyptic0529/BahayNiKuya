// Map functionality for Real Estate Listing System

document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('map-container');
    
    // Initialize map on homepage
    if (mapContainer) {
        // Create map centered on Los Angeles
        const map = L.map('map-container').setView([34.0522, -118.2437], 10);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Fetch properties for map
        fetch('api/properties.php?map=1')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Add property markers to map
                    data.properties.forEach(property => {
                        const marker = L.marker([property.latitude, property.longitude])
                            .addTo(map);
                        
                        // Create popup content
                        const popupContent = `
                            <div class="property-popup">
                                <img src="${property.image1}" alt="${property.title}">
                                <h3>${property.title}</h3>
                                <div class="price">$${Number(property.price).toLocaleString()}</div>
                                <div class="details">
                                    <span><i class="fas fa-bed"></i> ${property.bedrooms} Beds</span>
                                    <span><i class="fas fa-bath"></i> ${property.bathrooms} Baths</span>
                                    <span><i class="fas fa-ruler-combined"></i> ${property.area} sqft</span>
                                </div>
                                <a href="property_details.php?id=${property.id}" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        `;
                        
                        // Bind popup to marker
                        marker.bindPopup(popupContent);
                    });
                } else {
                    console.error('Error fetching properties:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Initialize map on property details page
    const propertyMap = document.getElementById('property-map');
    if (propertyMap) {
        const latitude = parseFloat(propertyMap.getAttribute('data-latitude'));
        const longitude = parseFloat(propertyMap.getAttribute('data-longitude'));
        const title = propertyMap.getAttribute('data-title');
        
        if (latitude && longitude) {
            // Create map centered on property
            const map = L.map('property-map').setView([latitude, longitude], 14);
            
            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Add marker for property
            const marker = L.marker([latitude, longitude]).addTo(map);
            
            // Add popup
            marker.bindPopup(`<strong>${title}</strong>`).openPopup();
        }
    }
});
