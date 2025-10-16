/**
 * خريطة المباني التفاعلية
 * Building Map Interactive Script
 */

class BuildingMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.buildings = [];
        this.infoWindow = null;
        this.defaultCenter = { lat: 24.7136, lng: 46.6753 }; // الرياض
    }

    /**
     * تهيئة الخريطة
     */
    initMap() {
        this.map = new google.maps.Map(document.getElementById("buildingsMap"), {
            zoom: 10,
            center: this.defaultCenter,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                },
                {
                    featureType: "transit",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ],
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true
        });

        this.infoWindow = new google.maps.InfoWindow();
        this.addMarkers();
        this.fitMapToMarkers();
    }

    /**
     * إضافة العلامات للمباني
     */
    addMarkers() {
        this.buildings.forEach((building) => {
            if (building.latitude && building.longitude) {
                const marker = new google.maps.Marker({
                    position: { 
                        lat: parseFloat(building.latitude), 
                        lng: parseFloat(building.longitude) 
                    },
                    map: this.map,
                    title: building.name_ar || building.name_en,
                    icon: this.createCustomIcon(),
                    animation: google.maps.Animation.DROP
                });

                // إضافة معلومات المبني عند النقر
                marker.addListener("click", () => {
                    this.showBuildingInfo(marker, building);
                });

                // تأثير عند التمرير
                marker.addListener("mouseover", () => {
                    marker.setIcon(this.createCustomIcon(true));
                });

                marker.addListener("mouseout", () => {
                    marker.setIcon(this.createCustomIcon(false));
                });

                this.markers.push(marker);
            }
        });
    }

    /**
     * إنشاء أيقونة مخصصة للعلامة
     */
    createCustomIcon(isHovered = false) {
        const color = isHovered ? '#e6a67a' : '#f7bb8e';
        const size = isHovered ? 45 : 40;
        
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="${size/2}" cy="${size/2}" r="${size/2 - 2}" fill="${color}" stroke="#fff" stroke-width="2"/>
                    <path d="M${size/2} ${size/4}c-2.5 0-4.5 2-4.5 4.5 0 3.5 4.5 7.5 4.5 7.5s4.5-4 4.5-7.5c0-2.5-2-4.5-4.5-4.5zm0 6c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5 .7 1.5 1.5-.7 1.5-1.5 1.5z" fill="#fff"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(size, size),
            anchor: new google.maps.Point(size/2, size/2)
        };
    }

    /**
     * عرض معلومات المبني
     */
    showBuildingInfo(marker, building) {
        const content = `
            <div class="p-4 max-w-xs">
                <div class="flex items-start space-x-3 rtl:space-x-reverse">
                    <div class="flex-shrink-0">
                        <img src="${building.image || '/assets/img/building-placeholder.jpg'}" 
                             alt="${building.name_ar || building.name_en}"
                             class="w-16 h-16 rounded-lg object-cover">
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg text-gray-900 mb-1">
                            ${building.name_ar || building.name_en}
                        </h3>
                        <p class="text-gray-600 text-sm mb-2">
                            <svg class="w-4 h-4 inline mr-1 rtl:ml-1 rtl:mr-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            ${building.city.name_ar || building.city.name_en}
                        </p>
                        <p class="text-gray-500 text-sm mb-3">
                            <svg class="w-4 h-4 inline mr-1 rtl:ml-1 rtl:mr-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            ${building.apartments_count || 0} ${window.langProperties || 'Properties'}
                        </p>
                        <a href="/building/${building.slug}" 
                           class="inline-flex items-center text-primary hover:text-primary-dark font-medium text-sm">
                            ${window.langViewDetails || 'View Details'}
                            <svg class="w-4 h-4 ml-1 rtl:mr-1 rtl:ml-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        `;

        this.infoWindow.setContent(content);
        this.infoWindow.open(this.map, marker);
    }

    /**
     * ضبط الخريطة لتشمل جميع العلامات
     */
    fitMapToMarkers() {
        if (this.markers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            this.markers.forEach((marker) => {
                bounds.extend(marker.getPosition());
            });
            this.map.fitBounds(bounds);
            
            // التأكد من أن مستوى التكبير ليس بعيداً جداً
            const listener = google.maps.event.addListener(this.map, "idle", () => {
                if (this.map.getZoom() > 15) {
                    this.map.setZoom(15);
                }
                google.maps.event.removeListener(listener);
            });
        }
    }

    /**
     * تحميل بيانات المباني
     */
    setBuildings(buildings) {
        this.buildings = buildings;
    }

    /**
     * البحث في المباني
     */
    searchBuildings(query) {
        const filteredBuildings = this.buildings.filter(building => {
            const name = (building.name_ar || building.name_en || '').toLowerCase();
            const city = (building.city.name_ar || building.city.name_en || '').toLowerCase();
            const searchQuery = query.toLowerCase();
            
            return name.includes(searchQuery) || city.includes(searchQuery);
        });

        // إخفاء جميع العلامات
        this.markers.forEach(marker => marker.setMap(null));
        this.markers = [];

        // إضافة العلامات المفلترة
        this.buildings = filteredBuildings;
        this.addMarkers();
        this.fitMapToMarkers();
    }

    /**
     * إعادة تعيين الخريطة
     */
    resetMap() {
        this.markers.forEach(marker => marker.setMap(null));
        this.markers = [];
        this.addMarkers();
        this.fitMapToMarkers();
    }
}

// تهيئة الخريطة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // التحقق من وجود عنصر الخريطة
    const mapElement = document.getElementById('buildingsMap');
    if (!mapElement) return;

    // إنشاء مثيل من فئة الخريطة
    window.buildingMap = new BuildingMap();
    
    // إعداد البحث
    setupSearch();
    
    // تحميل Google Maps API
    loadGoogleMapsAPI();
});

/**
 * إعداد وظيفة البحث
 */
function setupSearch() {
    const searchInput = document.getElementById('mapSearch');
    const clearButton = document.getElementById('clearSearch');
    
    if (!searchInput) return;
    
    let searchTimeout;
    
    // البحث عند الكتابة
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length > 0) {
            clearButton.classList.remove('hidden');
            searchTimeout = setTimeout(() => {
                if (window.buildingMap) {
                    window.buildingMap.searchBuildings(query);
                }
            }, 300);
        } else {
            clearButton.classList.add('hidden');
            if (window.buildingMap) {
                window.buildingMap.resetMap();
            }
        }
    });
    
    // مسح البحث
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            this.classList.add('hidden');
            if (window.buildingMap) {
                window.buildingMap.resetMap();
            }
        });
    }
}

/**
 * تحميل Google Maps API
 */
function loadGoogleMapsAPI() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCI5uwqK2Aa98cw1Jjnhzw6c-F9J5eSu7M&callback=initBuildingMap&libraries=places';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    } else {
        initBuildingMap();
    }
}

/**
 * دالة الاستدعاء لتهيئة الخريطة
 */
function initBuildingMap() {
    if (window.buildingMap && window.buildingsData) {
        window.buildingMap.setBuildings(window.buildingsData);
        window.buildingMap.initMap();
    }
}
