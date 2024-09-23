const locationInp= document.querySelector(".location-inp");
let selectedLatLng; 

function openLocationModal() {
    document.getElementById("locationModal").style.display = "block";
    initMap(); // Initialize the map when opening the modal
}

function closeLocationModal() {
    document.getElementById("locationModal").style.display = "none";
}

// Close the modal if the user clicks outside of it
window.onclick = function(event) {
    const modal = document.getElementById("locationModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
};

// Leaflet Map Initialization
function initMap() {
    const map = L.map('map').setView([27.7172, 85.3240], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    let marker;

    map.on('click', function (e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng).addTo(map);
        selectedLatLng = e.latlng; 
        locationInp.value = selectedLatLng.lat + "zzz" + selectedLatLng.lng;
    });
}