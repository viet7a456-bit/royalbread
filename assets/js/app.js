document.addEventListener('DOMContentLoaded', function () {
    const appConfig = window.RoyalBreadConfig || {};
    const searchApiUrl = appConfig.searchUrl || '/api/search';

    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('[data-nav-menu]');

    if (toggle && menu) {
        var closeNav = function () {
            menu.classList.remove('open');
            toggle.classList.remove('is-open');
        };

        toggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('open');
            toggle.classList.toggle('is-open', isOpen);
        });

        // Close nav when clicking a link inside
        menu.querySelectorAll('a, button[type="submit"]').forEach(function (el) {
            el.addEventListener('click', function () {
                setTimeout(closeNav, 80);
            });
        });

        // Close nav on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menu.classList.contains('open')) {
                closeNav();
            }
        });
    }

    /* ── Menu Category Tabs ── */
    const tabs = document.querySelectorAll('#menuCategoryTabs .mn-tab');
    const groups = document.querySelectorAll('.mn-group');
    const tabsWrap = document.querySelector('.mn-tabs-wrap');

    if (tabs.length > 0 && groups.length > 0) {
        const scrollToMenuTarget = function (element) {
            if (!element) {
                return;
            }

            const header = document.querySelector('.site-header');
            const headerHeight = header ? header.getBoundingClientRect().height : 0;
            const tabsHeight = tabsWrap ? tabsWrap.getBoundingClientRect().height : 0;
            const offset = headerHeight + tabsHeight + 18;
            const top = window.pageYOffset + element.getBoundingClientRect().top - offset;

            window.scrollTo({
                top: Math.max(top, 0),
                behavior: 'smooth',
            });
        };

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var filter = this.getAttribute('data-filter');

                tabs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');
                this.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center',
                });

                groups.forEach(function (group) {
                    group.classList.remove('hidden');
                });

                if (filter === 'all') {
                    scrollToMenuTarget(groups[0]);
                    return;
                }

                var targetGroup = document.querySelector('.mn-group[data-group="' + filter + '"]');
                scrollToMenuTarget(targetGroup);
            });
        });
    }

    /* ── Home Hero Visual Slider ── */
    const homeHero = document.querySelector('[data-home-hero]');
    if (homeHero) {
        const videoShell = homeHero.querySelector('[data-home-hero-video-shell]');
        const heroVideo = homeHero.querySelector('[data-home-hero-video]');
        const prevButton = homeHero.querySelector('[data-home-hero-prev]');
        const nextButton = homeHero.querySelector('[data-home-hero-next]');
        const dots = Array.from(homeHero.querySelectorAll('[data-home-hero-dot]'));
        let slides = [];

        try {
            slides = JSON.parse(homeHero.getAttribute('data-home-hero-slides') || '[]');
        } catch (error) {
            slides = [];
        }

        if (slides.length > 0) {
            let activeIndex = 0;
            let autoPlayTimer = null;
            let pointerStartX = null;

            const setFrameBackground = function (imageUrl) {
                if (!imageUrl) {
                    return;
                }

                const safeUrl = String(imageUrl).replace(/"/g, '\\"');
                homeHero.style.setProperty('--home-hero-bg', 'url("' + safeUrl + '")');
            };

            const stopVideo = function () {
                homeHero.classList.remove('has-video');

                if (videoShell) {
                    videoShell.classList.remove('is-active');
                    videoShell.hidden = true;
                }

                if (heroVideo) {
                    heroVideo.pause();
                    heroVideo.removeAttribute('src');
                    heroVideo.removeAttribute('poster');
                    heroVideo.load();
                }
            };

            const startVideo = function (slide) {
                if (!videoShell || !heroVideo || !slide.video) {
                    stopVideo();
                    return;
                }

                homeHero.classList.add('has-video');
                videoShell.hidden = false;
                videoShell.classList.remove('is-active');
                void videoShell.offsetWidth;
                videoShell.classList.add('is-active');

                if (slide.video_poster) {
                    heroVideo.poster = slide.video_poster;
                }

                if (heroVideo.getAttribute('src') !== slide.video) {
                    heroVideo.src = slide.video;
                    heroVideo.load();
                }

                heroVideo.playbackRate = Number(slide.video_rate) > 0 ? Number(slide.video_rate) : 0.78;

                try {
                    heroVideo.currentTime = 0;
                } catch (error) {
                    // Ignore when metadata is not ready yet.
                }

                const playPromise = heroVideo.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(function () {});
                }
            };

            const applySlide = function (nextIndex) {
                activeIndex = (nextIndex + slides.length) % slides.length;
                const slide = slides[activeIndex];

                const imageUrl = slide.background || slide.video_poster || slide.main || '';
                setFrameBackground(imageUrl);

                const fgImage = homeHero.querySelector('[data-home-hero-fg-image]');
                if (fgImage) {
                    fgImage.src = imageUrl;
                }

                const fgShell = homeHero.querySelector('[data-home-hero-image-shell]');

                if (slide.video) {
                    startVideo(slide);
                    if (fgShell) {
                        fgShell.hidden = true;
                    }
                    homeHero.classList.remove('has-image');
                } else {
                    stopVideo();
                    if (fgShell) {
                        fgShell.hidden = false;
                    }
                    homeHero.classList.add('has-image');
                }

                dots.forEach(function (dot, dotIndex) {
                    dot.classList.toggle('is-active', dotIndex === activeIndex);
                });
            };

            const stopAutoPlay = function () {
                if (autoPlayTimer !== null) {
                    clearTimeout(autoPlayTimer);
                    autoPlayTimer = null;
                }
            };

            const restartAutoPlay = function () {
                stopAutoPlay();

                if (slides.length > 1) {
                    const activeSlide = slides[activeIndex] || {};
                    const delay = Number(activeSlide.duration) > 0 ? Number(activeSlide.duration) : 7000;

                    autoPlayTimer = window.setTimeout(function () {
                        applySlide(activeIndex + 1);
                        restartAutoPlay();
                    }, delay);
                }
            };

            if (prevButton) {
                prevButton.addEventListener('click', function () {
                    applySlide(activeIndex - 1);
                    restartAutoPlay();
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    applySlide(activeIndex + 1);
                    restartAutoPlay();
                });
            }

            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    applySlide(Number(dot.getAttribute('data-home-hero-dot') || '0'));
                    restartAutoPlay();
                });
            });

            homeHero.addEventListener('pointerdown', function (event) {
                pointerStartX = event.clientX;
                homeHero.classList.add('is-dragging');
            });

            homeHero.addEventListener('pointerup', function (event) {
                if (pointerStartX === null) {
                    return;
                }

                const distance = event.clientX - pointerStartX;
                pointerStartX = null;
                homeHero.classList.remove('is-dragging');

                if (Math.abs(distance) < 42) {
                    return;
                }

                applySlide(activeIndex + (distance < 0 ? 1 : -1));
                restartAutoPlay();
            });

            homeHero.addEventListener('pointercancel', function () {
                pointerStartX = null;
                homeHero.classList.remove('is-dragging');
            });

            homeHero.addEventListener('mouseleave', function () {
                pointerStartX = null;
                homeHero.classList.remove('is-dragging');
            });

            applySlide(0);
            restartAutoPlay();
        }
    }

    const homeSpotlight = document.querySelector('[data-home-spotlight]');
    if (homeSpotlight) {
        const spotlightImage = homeSpotlight.querySelector('[data-home-spotlight-image]');
        const spotlightBadge = homeSpotlight.querySelector('[data-home-spotlight-badge]');
        const spotlightTitle = homeSpotlight.querySelector('[data-home-spotlight-title]');
        const spotlightDescription = homeSpotlight.querySelector('[data-home-spotlight-description]');
        const spotlightPrice = homeSpotlight.querySelector('[data-home-spotlight-price]');
        const spotlightChoices = Array.from(homeSpotlight.querySelectorAll('[data-home-spotlight-choice]'));

        const syncSpotlightTitleState = function (titleText) {
            const normalizedTitle = String(titleText || '').trim();
            const isLongTitle = normalizedTitle.length >= 24;
            homeSpotlight.classList.toggle('is-long-title', isLongTitle);
        };

        const applySpotlightChoice = function (choice) {
            if (!choice) {
                return;
            }

            const nextImage = choice.getAttribute('data-image') || '';
            const nextAlt = choice.getAttribute('data-alt') || '';
            const nextBadge = choice.getAttribute('data-badge') || '';
            const nextTitle = choice.getAttribute('data-title') || '';
            const nextDescription = choice.getAttribute('data-description') || '';
            const nextPrice = choice.getAttribute('data-price') || '';

            if (spotlightImage && nextImage) {
                spotlightImage.src = nextImage;
                spotlightImage.alt = nextAlt || nextTitle;
            }

            if (spotlightBadge) {
                spotlightBadge.textContent = nextBadge;
            }

            if (spotlightTitle) {
                spotlightTitle.textContent = nextTitle;
            }

            syncSpotlightTitleState(nextTitle);

            if (spotlightDescription) {
                spotlightDescription.textContent = nextDescription;
            }

            if (spotlightPrice) {
                spotlightPrice.textContent = nextPrice;
            }

            spotlightChoices.forEach(function (item) {
                const isActive = item === choice;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        spotlightChoices.forEach(function (choice) {
            choice.addEventListener('click', function () {
                applySpotlightChoice(choice);
            });
        });

        const initialSpotlightChoice = spotlightChoices.find(function (choice) {
            return choice.classList.contains('is-active');
        }) || spotlightChoices[0];

        if (initialSpotlightChoice) {
            applySpotlightChoice(initialSpotlightChoice);
        } else if (spotlightTitle) {
            syncSpotlightTitleState(spotlightTitle.textContent);
        }
    }

    const addonTabWrappers = document.querySelectorAll('[data-cart-addon-tabs]');
    addonTabWrappers.forEach(function (wrapper) {
        const tabs = Array.from(wrapper.querySelectorAll('[data-cart-addon-tab]'));
        const scope = wrapper.closest('.cart-addons') || document;
        const panes = Array.from(scope.querySelectorAll('[data-cart-addon-pane]'));

        const activatePane = function (key) {
            tabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.getAttribute('data-cart-addon-tab') === key);
            });

            panes.forEach(function (pane) {
                pane.classList.toggle('is-active', pane.getAttribute('data-cart-addon-pane') === key);
            });
        };

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activatePane(tab.getAttribute('data-cart-addon-tab'));
            });
        });
    });

    const paymentMethodSelect = document.querySelector('[data-payment-method-select]');
    const bankTransferInfo = document.querySelector('[data-bank-transfer-info]');
    if (paymentMethodSelect && bankTransferInfo) {
        const syncBankTransferInfo = function () {
            bankTransferInfo.hidden = !['bank_transfer', 'online_qr'].includes(paymentMethodSelect.value);
        };

        paymentMethodSelect.addEventListener('change', syncBankTransferInfo);
        syncBankTransferInfo();
    }

    if (false) {
    const cartAddressInput = document.querySelector('[data-delivery-address-input]');
    const cartDistanceInput = document.querySelector('[data-distance-km-input]');
    const cartSummary = document.querySelector('[data-cart-summary]');
    if (cartDistanceInput && cartSummary) {
        const storageKey = 'royalbread_cart_distance_km';
        const subtotal = Number(cartSummary.getAttribute('data-subtotal') || '0');
        const shippingRate = Number(cartSummary.getAttribute('data-shipping-rate') || '5000');
        const deliveryDistanceUrl = appConfig.deliveryDistanceUrl || '/api/delivery-distance';
        const shippingValue = cartSummary.querySelector('[data-cart-shipping-value]');
        const totalValue = cartSummary.querySelector('[data-cart-total-value]');
        const shippingNote = cartSummary.querySelector('[data-cart-shipping-note]');
        const distanceFeedback = document.querySelector('[data-distance-feedback]');
        let estimateTimer = null;
        let lastEstimatedAddress = '';

        const normalizeDistance = function (value) {
            const normalized = String(value || '').replace(',', '.').trim();
            const parsed = Number(normalized);
            if (!Number.isFinite(parsed) || parsed < 0) {
                return 0;
            }

            return Math.ceil(Math.round(parsed * 10000) / 1000) / 10;
        };

        const formatPrice = function (value) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(value)) + 'đ';
        };

        const formatDistance = function (value) {
            const normalized = normalizeDistance(value);
            return normalized.toLocaleString('vi-VN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }) + ' km';
        };

        const syncCartSummary = function () {
            const distanceKm = normalizeDistance(cartDistanceInput.value);
            const shippingFee = Math.round(distanceKm * shippingRate);
            const total = subtotal + shippingFee;

            if (shippingValue) {
                shippingValue.textContent = formatPrice(shippingFee);
            }

            if (totalValue) {
                totalValue.textContent = formatPrice(total);
            }

            if (shippingNote) {
                shippingNote.textContent = distanceKm > 0
                    ? formatDistance(distanceKm) + ' x ' + formatPrice(shippingRate) + '/km'
                    : 'Nhập số km để hệ thống tự tính phí ship 5.000đ / 1km.';
            }

            window.localStorage.setItem(storageKey, String(distanceKm));
        };

        const setDistanceFeedback = function (message, state) {
            if (!distanceFeedback) {
                return;
            }

            distanceFeedback.textContent = message;
            distanceFeedback.setAttribute('data-state', state || 'idle');
        };

        const estimateDeliveryDistance = function (force) {
            if (!cartAddressInput) {
                syncCartSummary();
                return;
            }

            const address = cartAddressInput.value.trim();
            if (address.length < 6) {
                lastEstimatedAddress = '';
                setDistanceFeedback('RoyalBread sẽ tự động tính khoảng cách theo địa chỉ giao hàng. Nếu cần, bạn vẫn có thể chỉnh tay số km.', 'idle');
                syncCartSummary();
                return;
            }

            if (!force && address === lastEstimatedAddress) {
                syncCartSummary();
                return;
            }

            setDistanceFeedback('RoyalBread đang tự tính khoảng cách giao hàng...', 'loading');

            fetch(deliveryDistanceUrl + '?address=' + encodeURIComponent(address), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (payload && payload.success) {
                        lastEstimatedAddress = address;
                        cartDistanceInput.value = String(payload.distance_km || '');
                        syncCartSummary();
                        setDistanceFeedback((payload.message || 'RoyalBread đã tự tính khoảng cách.') + ' ' + (payload.distance_text || ''), 'success');
                        return;
                    }

                    setDistanceFeedback(
                        (payload && payload.message) || 'Chưa tự tính được khoảng cách. Bạn có thể nhập tay số km để tiếp tục.',
                        'warning'
                    );
                    syncCartSummary();
                })
                .catch(function () {
                    setDistanceFeedback('RoyalBread chưa kết nối được dịch vụ tính khoảng cách. Bạn vẫn có thể nhập tay số km để đặt hàng.', 'warning');
                    syncCartSummary();
                });
        };

        const savedDistance = window.localStorage.getItem(storageKey);
        if ((cartDistanceInput.value || '').trim() === '' && savedDistance !== null) {
            cartDistanceInput.value = savedDistance;
        }

        cartDistanceInput.addEventListener('input', syncCartSummary);
        cartDistanceInput.addEventListener('change', syncCartSummary);
        syncCartSummary();

        if (cartAddressInput) {
            cartAddressInput.addEventListener('input', function () {
                clearTimeout(estimateTimer);
                estimateTimer = window.setTimeout(function () {
                    estimateDeliveryDistance(false);
                }, 700);
            });

            cartAddressInput.addEventListener('change', function () {
                estimateDeliveryDistance(true);
            });

            if (cartAddressInput.value.trim() !== '') {
                estimateDeliveryDistance(true);
            } else {
                setDistanceFeedback('RoyalBread sẽ tự động tính khoảng cách theo địa chỉ giao hàng. Nếu cần, bạn vẫn có thể chỉnh tay số km.', 'idle');
            }
        }
    }
    }

    const contactDirectionsButton = document.querySelector('[data-contact-directions]');
    const contactLocationStatus = document.querySelector('[data-contact-location-status]');
    if (contactDirectionsButton) {
        const mapOpenUrl = contactDirectionsButton.getAttribute('data-map-open-url') || '';
        const shopLat = Number(contactDirectionsButton.getAttribute('data-shop-lat') || '');
        const shopLon = Number(contactDirectionsButton.getAttribute('data-shop-lon') || '');

        const setContactLocationStatus = function (message, state) {
            if (!contactLocationStatus) {
                return;
            }

            contactLocationStatus.textContent = message || '';
            contactLocationStatus.setAttribute('data-state', state || 'idle');
        };

        contactDirectionsButton.addEventListener('click', function () {
            if (!navigator.geolocation) {
                setContactLocationStatus('Thiết bị này chưa hỗ trợ lấy vị trí hiện tại. RoyalBread sẽ mở bản đồ quán để bạn tự xem đường.', 'warning');
                if (mapOpenUrl) {
                    window.open(mapOpenUrl, '_blank', 'noopener');
                }
                return;
            }

            setContactLocationStatus('Đang lấy vị trí hiện tại của bạn để mở chỉ đường...', 'loading');

            navigator.geolocation.getCurrentPosition(function (position) {
                const currentLat = position.coords.latitude;
                const currentLon = position.coords.longitude;
                let directionsUrl = mapOpenUrl;

                if (Number.isFinite(shopLat) && Number.isFinite(shopLon)) {
                    directionsUrl = 'https://www.google.com/maps/dir/?api=1&origin='
                        + encodeURIComponent(currentLat + ',' + currentLon)
                        + '&destination='
                        + encodeURIComponent(shopLat + ',' + shopLon)
                        + '&travelmode=driving';
                }

                setContactLocationStatus('Đã lấy được vị trí hiện tại. RoyalBread đang mở chỉ đường cho bạn...', 'success');
                window.open(directionsUrl, '_blank', 'noopener');
            }, function (error) {
                const messageMap = {
                    1: 'Bạn đã từ chối quyền truy cập vị trí. RoyalBread sẽ mở bản đồ quán để bạn xem thủ công.',
                    2: 'Thiết bị chưa lấy được vị trí hiện tại. RoyalBread sẽ mở bản đồ quán để bạn xem thủ công.',
                    3: 'Hết thời gian lấy vị trí hiện tại. RoyalBread sẽ mở bản đồ quán để bạn xem thủ công.',
                };

                setContactLocationStatus(messageMap[error.code] || 'Chưa lấy được vị trí hiện tại. RoyalBread sẽ mở bản đồ quán để bạn xem thủ công.', 'warning');
                if (mapOpenUrl) {
                    window.open(mapOpenUrl, '_blank', 'noopener');
                }
            }, {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 60000,
            });
        });
    }

    const cartAddressInput = document.querySelector('[data-delivery-address-input]');
    const cartDistanceInput = document.querySelector('[data-distance-km-input]');
    const cartSummary = document.querySelector('[data-cart-summary]');
    if (cartDistanceInput && cartSummary) {
        const storageKey = 'royalbread_cart_distance_km';
        const subtotal = Number(cartSummary.getAttribute('data-subtotal') || '0');
        const shippingRate = Number(cartSummary.getAttribute('data-shipping-rate') || '5000');
        const deliveryDistanceUrl = appConfig.deliveryDistanceUrl || '/api/delivery-distance';
        const addressSuggestionsUrl = appConfig.addressSuggestionsUrl || '/api/address-suggestions';
        const reverseGeocodeUrl = appConfig.reverseGeocodeUrl || '/api/reverse-geocode';
        const shippingValue = cartSummary.querySelector('[data-cart-shipping-value]');
        const totalValue = cartSummary.querySelector('[data-cart-total-value]');
        const currentLocationButton = document.querySelector('[data-current-location-btn]');
        const suggestionBox = document.querySelector('[data-address-suggestions]');
        const locationStatus = document.querySelector('[data-location-status]');
        const latitudeInput = document.querySelector('[data-delivery-lat-input]');
        const longitudeInput = document.querySelector('[data-delivery-lon-input]');
        const resolvedAddressInput = document.querySelector('[data-resolved-address-input]');
        const recentLocationsKey = 'royalbread_recent_delivery_points';
        let estimateTimer = null;
        let suggestionTimer = null;
        let activeSuggestionRequest = 0;
        let lastEstimatedKey = '';

        const normalizeDistance = function (value) {
            const normalized = String(value || '').replace(',', '.').trim();
            const parsed = Number(normalized);
            if (!Number.isFinite(parsed) || parsed < 0) {
                return 0;
            }

            return Math.ceil(Math.round(parsed * 10000) / 1000) / 10;
        };

        const readCoordinate = function (value) {
            const normalized = String(value || '').replace(',', '.').trim();
            if (normalized === '') {
                return null;
            }

            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : null;
        };

        const formatPrice = function (value) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(value)) + 'đ';
        };

        const syncCartSummary = function () {
            const distanceKm = normalizeDistance(cartDistanceInput.value);
            const shippingFee = Math.round(distanceKm * shippingRate);
            const total = subtotal + shippingFee;

            if (shippingValue) {
                shippingValue.textContent = formatPrice(shippingFee);
            }

            if (totalValue) {
                totalValue.textContent = formatPrice(total);
            }

            window.localStorage.setItem(storageKey, String(distanceKm));
        };

        const setLocationStatus = function (message, state) {
            if (!locationStatus) {
                return;
            }

            locationStatus.textContent = message || '';
            locationStatus.hidden = !message;
            locationStatus.setAttribute('data-state', state || 'idle');
        };

        const clearSavedCoordinates = function () {
            if (latitudeInput) {
                latitudeInput.value = '';
            }

            if (longitudeInput) {
                longitudeInput.value = '';
            }

            if (resolvedAddressInput) {
                resolvedAddressInput.value = '';
            }
        };

        const loadRecentLocations = function () {
            try {
                const rawValue = window.localStorage.getItem(recentLocationsKey);
                const parsedValue = rawValue ? JSON.parse(rawValue) : [];
                if (!Array.isArray(parsedValue)) {
                    return [];
                }

                return parsedValue.filter(function (item) {
                    return item && typeof item === 'object' && item.label && item.short_label;
                });
            } catch (error) {
                return [];
            }
        };

        const saveRecentLocation = function (location) {
            if (!location || !location.label || !location.short_label) {
                return;
            }

            const normalizedLabel = String(location.label).trim().toLowerCase();
            if (normalizedLabel === '') {
                return;
            }

            const nextLocations = loadRecentLocations().filter(function (item) {
                return String(item.label || '').trim().toLowerCase() !== normalizedLabel;
            });

            nextLocations.unshift({
                label: String(location.label || '').trim(),
                short_label: String(location.short_label || location.label || '').trim(),
                lat: Number(location.lat || 0),
                lon: Number(location.lon || 0),
            });

            window.localStorage.setItem(recentLocationsKey, JSON.stringify(nextLocations.slice(0, 5)));
        };

        const normalizedText = function (value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, ' ')
                .trim();
        };

        const hideSuggestions = function () {
            if (!suggestionBox) {
                return;
            }

            suggestionBox.hidden = true;
            suggestionBox.innerHTML = '';
        };

        const getEstimateKey = function () {
            const latitude = readCoordinate(latitudeInput ? latitudeInput.value : '');
            const longitude = readCoordinate(longitudeInput ? longitudeInput.value : '');

            if (latitude !== null && longitude !== null) {
                return 'coords:' + latitude.toFixed(5) + ':' + longitude.toFixed(5);
            }

            return 'address:' + String(cartAddressInput ? cartAddressInput.value : '').trim().toLowerCase();
        };

        const recentSuggestionsForQuery = function (query) {
            const normalizedQuery = normalizedText(query);
            return loadRecentLocations().filter(function (item) {
                if (normalizedQuery === '') {
                    return true;
                }

                const haystack = normalizedText((item.short_label || '') + ' ' + (item.label || ''));
                return haystack.includes(normalizedQuery);
            });
        };

        const renderSuggestions = function (suggestions) {
            if (!suggestionBox) {
                return;
            }

            if (!Array.isArray(suggestions) || suggestions.length === 0) {
                hideSuggestions();
                return;
            }

            suggestionBox.innerHTML = '';

            suggestions.forEach(function (suggestion) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'checkout-location-suggestion';

                const title = document.createElement('strong');
                title.textContent = suggestion.short_label || suggestion.label || 'Điểm nhận hàng';

                const subtitle = document.createElement('span');
                subtitle.textContent = suggestion.label || '';

                button.appendChild(title);
                button.appendChild(subtitle);
                button.addEventListener('click', function () {
                    if (cartAddressInput) {
                        cartAddressInput.value = suggestion.label || '';
                    }

                    if (resolvedAddressInput) {
                        resolvedAddressInput.value = suggestion.label || '';
                    }

                    if (latitudeInput) {
                        latitudeInput.value = suggestion.lat || '';
                    }

                    if (longitudeInput) {
                        longitudeInput.value = suggestion.lon || '';
                    }

                    saveRecentLocation(suggestion);
                    hideSuggestions();
                    setLocationStatus('Đã chọn điểm nhận hàng: ' + (suggestion.short_label || suggestion.label || ''), 'success');
                    lastEstimatedKey = '';
                    estimateDeliveryDistance(true);
                });

                suggestionBox.appendChild(button);
            });

            suggestionBox.hidden = false;
        };

        const fetchSuggestions = function (allowShortQuery) {
            if (!cartAddressInput || !suggestionBox) {
                return;
            }

            const query = cartAddressInput.value.trim();
            const recentMatches = recentSuggestionsForQuery(query);

            if (query.length < 3 && recentMatches.length > 0) {
                renderSuggestions(recentMatches);
                return;
            }

            if (query.length < 3 && !allowShortQuery) {
                hideSuggestions();
                return;
            }

            const requestId = ++activeSuggestionRequest;
            fetch(addressSuggestionsUrl + '?q=' + encodeURIComponent(query), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (requestId !== activeSuggestionRequest) {
                        return;
                    }

                    const apiSuggestions = payload && Array.isArray(payload.suggestions) ? payload.suggestions : [];
                    if (apiSuggestions.length > 0) {
                        renderSuggestions(apiSuggestions);
                        return;
                    }

                    if (recentMatches.length > 0) {
                        renderSuggestions(recentMatches);
                        return;
                    }

                    hideSuggestions();
                })
                .catch(function () {
                    if (requestId !== activeSuggestionRequest) {
                        return;
                    }

                    if (recentMatches.length > 0) {
                        renderSuggestions(recentMatches);
                        return;
                    }

                    hideSuggestions();
                });
        };

        const estimateDeliveryDistance = function (force) {
            if (!cartAddressInput) {
                syncCartSummary();
                return;
            }

            const address = cartAddressInput.value.trim();
            const latitude = readCoordinate(latitudeInput ? latitudeInput.value : '');
            const longitude = readCoordinate(longitudeInput ? longitudeInput.value : '');
            const hasCoordinates = latitude !== null && longitude !== null;

            if (!hasCoordinates && address.length < 6) {
                lastEstimatedKey = '';
                syncCartSummary();
                return;
            }

            const estimateKey = getEstimateKey();
            if (!force && estimateKey === lastEstimatedKey) {
                syncCartSummary();
                return;
            }

            const params = new URLSearchParams();
            if (hasCoordinates) {
                params.set('lat', String(latitude));
                params.set('lon', String(longitude));
                if (address !== '') {
                    params.set('label', address);
                }
                setLocationStatus('RoyalBread đang tính quãng đường từ vị trí bạn đã chọn...', 'loading');
            } else {
                params.set('address', address);
                setLocationStatus('RoyalBread đang nhận diện địa chỉ giao hàng của bạn...', 'loading');
            }

            fetch(deliveryDistanceUrl + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (payload && payload.success) {
                        lastEstimatedKey = estimateKey;
                        cartDistanceInput.value = String(payload.distance_km || '');

                        if (resolvedAddressInput && payload.resolved_customer_address) {
                            resolvedAddressInput.value = payload.resolved_customer_address;
                        }

                        if (latitudeInput && payload.customer_lat) {
                            latitudeInput.value = payload.customer_lat;
                        }

                        if (longitudeInput && payload.customer_lon) {
                            longitudeInput.value = payload.customer_lon;
                        }

                        saveRecentLocation({
                            label: payload.resolved_customer_address || address,
                            short_label: payload.resolved_customer_address || address,
                            lat: payload.customer_lat || latitude,
                            lon: payload.customer_lon || longitude,
                        });
                        syncCartSummary();
                        setLocationStatus(payload.message || 'RoyalBread đã tự tính được khoảng cách giao hàng.', 'success');
                        return;
                    }

                    syncCartSummary();
                    setLocationStatus(
                        (payload && payload.message) || 'Chưa tự tính được khoảng cách. Bạn có thể chọn gợi ý khác hoặc chỉnh tay số km.',
                        'warning'
                    );
                })
                .catch(function () {
                    syncCartSummary();
                    setLocationStatus('RoyalBread chưa kết nối được dịch vụ bản đồ. Bạn vẫn có thể nhập tay số km để tiếp tục.', 'warning');
                });
        };

        const savedDistance = window.localStorage.getItem(storageKey);
        if ((cartDistanceInput.value || '').trim() === '' && savedDistance !== null) {
            cartDistanceInput.value = savedDistance;
        }

        // Distance field is readonly - only listen for programmatic changes
        cartDistanceInput.addEventListener('change', syncCartSummary);
        syncCartSummary();

        if (cartAddressInput) {
            cartAddressInput.addEventListener('input', function () {
                clearSavedCoordinates();
                lastEstimatedKey = '';

                clearTimeout(suggestionTimer);
                suggestionTimer = window.setTimeout(fetchSuggestions, 260);

                clearTimeout(estimateTimer);
                estimateTimer = window.setTimeout(function () {
                    estimateDeliveryDistance(false);
                }, 700);
            });

            cartAddressInput.addEventListener('focus', function () {
                if (cartAddressInput.value.trim() === '') {
                    fetchSuggestions(true);
                    return;
                }

                const recentMatches = recentSuggestionsForQuery(cartAddressInput.value.trim());
                if (recentMatches.length > 0) {
                    renderSuggestions(recentMatches);
                }
            });

            cartAddressInput.addEventListener('change', function () {
                estimateDeliveryDistance(true);
            });
        }

        if (suggestionBox && cartAddressInput) {
            document.addEventListener('click', function (event) {
                if (!suggestionBox.contains(event.target) && !cartAddressInput.contains(event.target)) {
                    hideSuggestions();
                }
            });
        }

        if (currentLocationButton) {
            currentLocationButton.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    setLocationStatus('Thiết bị này chưa hỗ trợ lấy vị trí hiện tại. Bạn hãy nhập địa chỉ hoặc chọn từ gợi ý nhé.', 'warning');
                    return;
                }

                setLocationStatus('RoyalBread đang lấy vị trí hiện tại của bạn...', 'loading');

                navigator.geolocation.getCurrentPosition(function (position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;

                    if (latitudeInput) {
                        latitudeInput.value = String(latitude);
                    }

                    if (longitudeInput) {
                        longitudeInput.value = String(longitude);
                    }

                    fetch(reverseGeocodeUrl + '?lat=' + encodeURIComponent(String(latitude)) + '&lon=' + encodeURIComponent(String(longitude)), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (payload) {
                            if (payload && payload.success) {
                                if (cartAddressInput) {
                                    cartAddressInput.value = payload.label || '';
                                }

                                if (resolvedAddressInput) {
                                    resolvedAddressInput.value = payload.label || '';
                                }

                                saveRecentLocation({
                                    label: payload.label || '',
                                    short_label: payload.short_label || payload.label || '',
                                    lat: latitude,
                                    lon: longitude,
                                });
                                hideSuggestions();
                                setLocationStatus('Đã lấy vị trí hiện tại: ' + (payload.short_label || payload.label || ''), 'success');
                                lastEstimatedKey = '';
                                estimateDeliveryDistance(true);
                                return;
                            }

                            if (cartAddressInput && cartAddressInput.value.trim() === '') {
                                cartAddressInput.value = 'Vị trí hiện tại của khách';
                            }

                            if (resolvedAddressInput) {
                                resolvedAddressInput.value = cartAddressInput ? cartAddressInput.value.trim() : '';
                            }

                            setLocationStatus(
                                (payload && payload.message) || 'Đã lấy được tọa độ hiện tại, nhưng chưa nhận diện rõ địa chỉ. Bạn vẫn có thể đặt hàng hoặc chọn lại gợi ý.',
                                'warning'
                            );
                            lastEstimatedKey = '';
                            estimateDeliveryDistance(true);
                        })
                        .catch(function () {
                            if (cartAddressInput && cartAddressInput.value.trim() === '') {
                                cartAddressInput.value = 'Vị trí hiện tại của khách';
                            }

                            if (resolvedAddressInput) {
                                resolvedAddressInput.value = cartAddressInput ? cartAddressInput.value.trim() : '';
                            }

                            setLocationStatus('Đã lấy được vị trí hiện tại. RoyalBread sẽ dùng vị trí này để tính quãng đường giao hàng.', 'success');
                            lastEstimatedKey = '';
                            estimateDeliveryDistance(true);
                        });
                }, function (error) {
                    const messageMap = {
                        1: 'Bạn đã từ chối quyền truy cập vị trí. Bạn vẫn có thể nhập địa chỉ hoặc chọn từ gợi ý.',
                        2: 'Thiết bị chưa lấy được vị trí hiện tại. Bạn hãy nhập địa chỉ cụ thể hoặc thử lại sau.',
                        3: 'Hết thời gian lấy vị trí hiện tại. Bạn hãy thử lại hoặc nhập địa chỉ thủ công.',
                    };

                    setLocationStatus(messageMap[error.code] || 'Chưa lấy được vị trí hiện tại. Bạn hãy nhập địa chỉ hoặc chọn từ gợi ý nhé.', 'warning');
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 60000,
                });
            });
        }

        if (cartAddressInput && cartAddressInput.value.trim() !== '') {
            estimateDeliveryDistance(true);
        } else {
            setLocationStatus('', 'idle');
        }
    }

    // Checkout form validation - require coordinates or resolved address (no manual distance)
    const checkoutForm = document.getElementById('cartCheckoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (event) {
            const latInput = checkoutForm.querySelector('[name="delivery_lat"]');
            const lonInput = checkoutForm.querySelector('[name="delivery_lon"]');
            const resolvedInput = checkoutForm.querySelector('[name="resolved_address"]');

            const hasCoords = latInput && lonInput && latInput.value.trim() !== '' && lonInput.value.trim() !== '';
            const hasResolved = resolvedInput && resolvedInput.value.trim() !== '';

            if (!hasCoords && !hasResolved) {
                event.preventDefault();
                alert('Vui lòng chọn địa chỉ từ gợi ý hoặc dùng vị trí hiện tại để RoyalBread tính được phí giao hàng chính xác.');

                const addressInput = checkoutForm.querySelector('[name="address"]');
                if (addressInput) {
                    addressInput.focus();
                    addressInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
        });
    }

    // Flash Messages Auto-dismiss
    const flashes = document.querySelectorAll('.flash');
    if (flashes.length > 0) {
        setTimeout(() => {
            flashes.forEach(flash => {
                flash.style.opacity = '0';
                flash.style.transform = 'translateY(-10px)';
                setTimeout(() => flash.remove(), 300);
            });
        }, 5000);
    }

    // Live Search
    const searchInput = document.getElementById('liveSearchInput');
    const searchResults = document.getElementById('liveSearchResults');
    if (searchInput && searchResults) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length === 0) {
                searchResults.style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(() => {
                fetch(searchApiUrl + '?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (data.results && data.results.length > 0) {
                            const rawBase = appConfig.baseUrl || '/';
                            const baseUrl = rawBase.endsWith('/') ? rawBase : rawBase + '/';
                            searchResults.innerHTML = data.results.map(item => `
                                <div class="search-result-item" style="display:flex; gap:10px; padding:10px; border-bottom:1px solid #eee; align-items:center;">
                                    <a href="${baseUrl}menu#menu-item-${item.id}" class="search-result-link" style="display:flex; gap:10px; flex:1; align-items:center; text-decoration:none; color:inherit;">
                                        <img src="${item.image_url}" alt="${item.name}" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                                        <div class="search-result-info" style="flex:1;">
                                            <strong style="color:#3a2415; display:block;">${item.name}</strong>
                                            <span style="font-size:0.8rem; color:#8a6c4e;">${item.category_name}</span>
                                            <div class="search-result-price" style="color:#d4943a; font-weight:bold;">${item.price}</div>
                                        </div>
                                    </a>
                                    <div style="display:grid; gap:6px;">
                                        <form method="post" action="${appConfig.cartAddUrl || '/cart/add'}" style="margin:0;">
                                            <input type="hidden" name="_csrf_token" value="${appConfig.csrfToken || ''}">
                                            <input type="hidden" name="id" value="${item.id}">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="redirect_to" value="">
                                            <button type="submit" class="btn btn-outline" style="padding:5px 10px; font-size:12px;">Thêm giỏ</button>
                                        </form>
                                        <form method="post" action="${appConfig.buyNowUrl || '/cart/buy-now'}" style="margin:0;">
                                            <input type="hidden" name="_csrf_token" value="${appConfig.csrfToken || ''}">
                                            <input type="hidden" name="id" value="${item.id}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-primary" style="padding:5px 10px; font-size:12px;">Đặt ngay</button>
                                        </form>
                                    </div>
                                </div>
                            `).join('');
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div style="padding:15px;text-align:center;color:#666;">Không tìm thấy món ăn phù hợp.</div>';
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query.length === 0) return;

                const firstLink = searchResults.querySelector('.search-result-link');
                if (firstLink && searchResults.style.display !== 'none') {
                    window.location.href = firstLink.href;
                } else {
                    clearTimeout(debounceTimer);
                    fetch(searchApiUrl + '?q=' + encodeURIComponent(query))
                        .then(res => res.json())
                        .then(data => {
                            if (data.results && data.results.length > 0) {
                                const item = data.results[0];
                                const rawBase = appConfig.baseUrl || '/';
                                const baseUrl = rawBase.endsWith('/') ? rawBase : rawBase + '/';
                                window.location.href = baseUrl + 'menu#menu-item-' + item.id;
                            } else {
                                const rawBase = appConfig.baseUrl || '/';
                                const baseUrl = rawBase.endsWith('/') ? rawBase : rawBase + '/';
                                window.location.href = baseUrl + 'menu';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            const rawBase = appConfig.baseUrl || '/';
                            const baseUrl = rawBase.endsWith('/') ? rawBase : rawBase + '/';
                            window.location.href = baseUrl + 'menu';
                        });
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // Smooth scroll to menu-item when URL hash matches #menu-item-X or category #group-X
    function handleHashScroll() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#menu-item-')) {
            const itemId = hash.substring(11);
            const element = document.getElementById('menu-item-' + itemId);
            if (element) {
                // Highlight item
                element.classList.add('mn-card--highlighted');
                setTimeout(() => {
                    element.classList.remove('mn-card--highlighted');
                }, 4600);

                setTimeout(() => {
                    // Activate corresponding category tab
                    const group = element.closest('.mn-group');
                    if (group) {
                        const filter = group.getAttribute('data-group');
                        const correspondingTab = document.querySelector('#menuCategoryTabs .mn-tab[data-filter="' + filter + '"]');
                        if (correspondingTab) {
                            document.querySelectorAll('#menuCategoryTabs .mn-tab').forEach(t => t.classList.remove('active'));
                            correspondingTab.classList.add('active');
                            correspondingTab.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest',
                                inline: 'center',
                            });
                        }
                    }

                    // Scroll to item with offset
                    const header = document.querySelector('.site-header');
                    const headerHeight = header ? header.getBoundingClientRect().height : 0;
                    const tabsWrap = document.querySelector('.mn-tabs-wrap');
                    const tabsHeight = tabsWrap ? tabsWrap.getBoundingClientRect().height : 0;
                    const offset = headerHeight + tabsHeight + 18;
                    const top = window.pageYOffset + element.getBoundingClientRect().top - offset;

                    window.scrollTo({
                        top: Math.max(top, 0),
                        behavior: 'smooth',
                    });
                }, 200);
            }
        } else if (hash && hash.startsWith('#group-')) {
            const groupFilter = hash.substring(7);
            const targetGroup = document.querySelector('.mn-group[data-group="' + groupFilter + '"]');
            if (targetGroup) {
                const correspondingTab = document.querySelector('#menuCategoryTabs .mn-tab[data-filter="' + groupFilter + '"]');
                if (correspondingTab) {
                    if (correspondingTab.classList.contains('active')) {
                        return;
                    }
                    document.querySelectorAll('#menuCategoryTabs .mn-tab').forEach(t => t.classList.remove('active'));
                    correspondingTab.classList.add('active');
                    correspondingTab.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'center',
                    });
                }

                setTimeout(() => {
                    const header = document.querySelector('.site-header');
                    const headerHeight = header ? header.getBoundingClientRect().height : 0;
                    const tabsWrap = document.querySelector('.mn-tabs-wrap');
                    const tabsHeight = tabsWrap ? tabsWrap.getBoundingClientRect().height : 0;
                    const offset = headerHeight + tabsHeight + 18;
                    const top = window.pageYOffset + targetGroup.getBoundingClientRect().top - offset;

                    window.scrollTo({
                        top: Math.max(top, 0),
                        behavior: 'smooth',
                    });
                }, 200);
            }
        }
    }

    handleHashScroll();
    window.addEventListener('hashchange', handleHashScroll);

    // Dynamic sliding indicator for desktop site-nav
    const nav = document.querySelector('.site-nav');
    if (nav) {
        const indicator = nav.querySelector('.site-nav__indicator');
        const navLinks = Array.from(nav.querySelectorAll('a:not(.site-nav__cart):not(.site-nav__account)'));
        const activeLink = navLinks.find(link => link.classList.contains('active')) || navLinks[0];

        const updateIndicator = (link) => {
            if (!link || window.innerWidth <= 860) {
                if (indicator) indicator.style.opacity = '0';
                return;
            }
            const navRect = nav.getBoundingClientRect();
            const linkRect = link.getBoundingClientRect();

            if (indicator) {
                indicator.style.left = `${linkRect.left - navRect.left}px`;
                indicator.style.width = `${linkRect.width}px`;
                indicator.style.opacity = '1';
            }
        };

        // Update position on load and resize
        setTimeout(() => updateIndicator(activeLink), 150);

        navLinks.forEach(link => {
            link.addEventListener('mouseenter', () => updateIndicator(link));
        });

        nav.addEventListener('mouseleave', () => {
            const currentActive = navLinks.find(l => l.classList.contains('active')) || activeLink;
            updateIndicator(currentActive);
        });

        window.addEventListener('resize', () => {
            const currentActive = navLinks.find(l => l.classList.contains('active')) || activeLink;
            updateIndicator(currentActive);
        });
    }

    initChatbot(appConfig);
});

function initChatbot(appConfig) {
    const lottieScriptSrc = 'https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js';
    const lottieAnimationSrc = 'https://lottie.host/28d3afb1-ec05-4776-86fa-3d7df31d73c9/L8is0SIg9N.lottie';

    if (!document.querySelector('script[data-chatbot-lottie]')) {
        const lottieScript = document.createElement('script');
        lottieScript.type = 'module';
        lottieScript.src = lottieScriptSrc;
        lottieScript.setAttribute('data-chatbot-lottie', 'true');
        document.head.appendChild(lottieScript);
    }

    const chatbotHtml = `
        <div class="chatbot-widget" id="chatbotWidget">
            <button class="chatbot-toggle" id="chatbotToggle" aria-label="Mở chat">
                <dotlottie-wc class="chatbot-toggle__lottie" src="${lottieAnimationSrc}" autoplay loop></dotlottie-wc>
            </button>
            <div class="chatbot-window" id="chatbotWindow" style="display: none;">
                <div class="chatbot-header">
                    <strong>Trợ lý AI RoyalBread</strong>
                    <button class="chatbot-close" id="chatbotClose">&times;</button>
                </div>
                <div class="chatbot-body" id="chatbotBody">
                    <div class="chat-message bot-message">
                        <div class="chat-message__bubble">
                            Chào bạn! Mình là trợ lý RoyalBread. Bạn có thể hỏi về món ăn, giá, best seller, món theo ngân sách, giờ mở cửa, địa chỉ, giỏ hàng hoặc đơn gần đây.
                        </div>
                        <div class="chatbot-suggestions">
                            <button type="button" class="chatbot-suggestion-chip" data-chat-suggestion="Món bán chạy">Món bán chạy 🔥</button>
                            <button type="button" class="chatbot-suggestion-chip" data-chat-suggestion="Gợi ý ăn no bụng">Ăn no bụng 🍳</button>
                            <button type="button" class="chatbot-suggestion-chip" data-chat-suggestion="Đồ uống">Đồ uống 🥤</button>
                            <button type="button" class="chatbot-suggestion-chip" data-chat-suggestion="Món dưới 30k">Món dưới 30k 💰</button>
                            <button type="button" class="chatbot-suggestion-chip" data-chat-suggestion="Địa chỉ quán">Địa chỉ & Giờ mở cửa 📍</button>
                        </div>
                    </div>
                </div>
                <div class="chatbot-input">
                    <input type="text" id="chatInput" placeholder="Nhập câu hỏi...">
                    <button id="chatSend">Gửi</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', chatbotHtml);

    const toggle = document.getElementById('chatbotToggle');
    const win = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatBody = document.getElementById('chatbotBody');
    const chatbotUrl = appConfig.chatbotUrl || '/api/assistant';
    const cartAddUrl = appConfig.cartAddUrl || '/cart/add';
    const storageKey = 'royalbread_chat_history';

    const escapeHtml = function (value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const renderBotMessage = function (payload) {
        const answer = escapeHtml(payload.answer || '').replace(/\n/g, '<br>');
        let html = `
            <div class="chat-message bot-message">
                <div class="chat-message__bubble">${answer}</div>
        `;

        if (Array.isArray(payload.products) && payload.products.length > 0) {
            html += '<div class="chatbot-products">';
            payload.products.forEach(function (item) {
                html += `
                    <article class="chatbot-product-card">
                        <img src="${escapeHtml(item.image_url || '')}" alt="${escapeHtml(item.name || '')}">
                        <div class="chatbot-product-card__body">
                            <span>${escapeHtml(item.category_name || 'RoyalBread')}</span>
                            <strong>${escapeHtml(item.name || '')}</strong>
                            <p>${escapeHtml(item.price || '')}</p>
                        </div>
                        <div class="chatbot-product-card__actions">
                            <form method="post" action="${escapeHtml(cartAddUrl)}" class="chatbot-product-card__form">
                                <input type="hidden" name="_csrf_token" value="${escapeHtml(appConfig.csrfToken || '')}">
                                <input type="hidden" name="id" value="${escapeHtml(item.id || '')}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="chatbot-product-card__btn chatbot-product-card__btn--ghost" title="Thêm vào giỏ hàng">Thêm</button>
                            </form>
                            <form method="post" action="${escapeHtml(appConfig.buyNowUrl || '/cart/buy-now')}" class="chatbot-product-card__form">
                                <input type="hidden" name="_csrf_token" value="${escapeHtml(appConfig.csrfToken || '')}">
                                <input type="hidden" name="id" value="${escapeHtml(item.id || '')}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="chatbot-product-card__btn" title="Đặt giao hàng ngay">Đặt ngay</button>
                            </form>
                        </div>
                    </article>
                `;
            });
            html += '</div>';
        }

        if (Array.isArray(payload.suggestions) && payload.suggestions.length > 0) {
            html += '<div class="chatbot-suggestions">';
            payload.suggestions.forEach(function (suggestion) {
                html += `<button type="button" class="chatbot-suggestion-chip" data-chat-suggestion="${escapeHtml(suggestion)}">${escapeHtml(suggestion)}</button>`;
            });
            html += '</div>';
        }

        html += '</div>';
        chatBody.insertAdjacentHTML('beforeend', html);
        chatBody.scrollTop = chatBody.scrollHeight;
    };

    const saveHistory = function () {
        window.sessionStorage.setItem(storageKey, chatBody.innerHTML);
    };

    const restoreHistory = function () {
        const saved = window.sessionStorage.getItem(storageKey);
        if (saved) {
            chatBody.innerHTML = saved;
            // Update restored CSRF inputs to the current token in memory
            chatBody.querySelectorAll('input[name="_csrf_token"]').forEach(input => {
                input.value = appConfig.csrfToken || '';
            });
        }
    };

    toggle.addEventListener('click', () => {
        win.style.display = 'flex';
        toggle.style.display = 'none';
        chatInput.focus();
    });

    closeBtn.addEventListener('click', () => {
        win.style.display = 'none';
        toggle.style.display = 'flex';
    });

    const bindSuggestionChips = function () {
        chatBody.querySelectorAll('[data-chat-suggestion]').forEach(function (button) {
            button.setAttribute('type', 'button');
        });
    };

    function sendMessage(prefilledText) {
        const text = (typeof prefilledText === 'string' ? prefilledText : chatInput.value).trim();
        if (text === '') return;

        chatBody.insertAdjacentHTML('beforeend', `<div class="chat-message user-message"><div class="chat-message__bubble">${escapeHtml(text)}</div></div>`);
        chatInput.value = '';
        chatBody.scrollTop = chatBody.scrollHeight;
        saveHistory();

        const typingId = 'typing-' + Date.now();
        chatBody.insertAdjacentHTML(
            'beforeend',
            `<div class="chat-message bot-message" id="${typingId}"><div class="chat-message__bubble chatbot-typing">RoyalBread đang soạn trả lời...</div></div>`
        );
        chatBody.scrollTop = chatBody.scrollHeight;

        fetch(chatbotUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({ message: text }).toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                const typingNode = document.getElementById(typingId);
                if (typingNode) {
                    typingNode.remove();
                }

                renderBotMessage(payload);
                bindSuggestionChips();
                saveHistory();
            })
            .catch(function () {
                const typingNode = document.getElementById(typingId);
                if (typingNode) {
                    typingNode.remove();
                }

                renderBotMessage({
                    answer: 'Hiện mình chưa kết nối được tới trợ lý RoyalBread. Bạn thử lại sau ít phút hoặc gọi hotline để quán hỗ trợ nhanh hơn.',
                    suggestions: ['Hotline', 'Địa chỉ quán', 'Món bán chạy'],
                    products: []
                });
                bindSuggestionChips();
                saveHistory();
            });
    }

    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    chatBody.addEventListener('click', function (event) {
        const suggestionButton = event.target.closest('[data-chat-suggestion]');
        if (!suggestionButton) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        sendMessage(suggestionButton.getAttribute('data-chat-suggestion') || '');
    });

    function submitCartForm(form, btn, originalText, attempt = 1) {
        let csrfInput = form.querySelector('input[name="_csrf_token"]');
        if (!csrfInput) {
            csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf_token';
            form.appendChild(csrfInput);
        }
        csrfInput.value = appConfig.csrfToken || '';

        const formData = new FormData(form);
        const params = new URLSearchParams();
        for (const pair of formData.entries()) {
            params.append(pair[0], pair[1]);
        }

        return fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString()
        })
        .then(response => {
            if (response.status === 419 && attempt === 1) {
                // CSRF token expired/mismatch. Try updating token and retrying once.
                return response.json().then(data => {
                    if (data.csrf_token) {
                        appConfig.csrfToken = data.csrf_token;
                        document.querySelectorAll('input[name="_csrf_token"]').forEach(input => {
                            input.value = data.csrf_token;
                        });
                        // Retry silently
                        return submitCartForm(form, btn, originalText, 2);
                    }
                    throw new Error(data.message || 'Phiên làm việc đã hết hạn.');
                });
            }
            if (!response.ok) throw new Error('Yêu cầu không thành công');
            return response.json();
        })
        .then(data => {
            if (!data) return; // Nested call handled it
            if (data.success) {
                if (data.redirect && form.action.includes('buy-now')) {
                    window.location.href = data.redirect;
                    return;
                }

                btn.textContent = 'Đã thêm ✓';
                btn.classList.add('added');

                if (data.csrf_token) {
                    appConfig.csrfToken = data.csrf_token;
                    document.querySelectorAll('input[name="_csrf_token"]').forEach(input => {
                        input.value = data.csrf_token;
                    });
                }

                const cartCount = data.cart_count;
                if (typeof cartCount !== 'undefined') {
                    const cartLink = document.querySelector('.site-nav__cart');
                    if (cartLink) {
                        let badge = cartLink.querySelector('.site-nav__cart-badge');
                        if (cartCount > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'site-nav__cart-badge';
                                cartLink.appendChild(badge);
                            }
                            badge.textContent = cartCount;
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                }

                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    btn.classList.remove('added');
                }, 2000);
            } else {
                alert(data.message || 'Không thể thêm món vào giỏ hàng.');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(err => {
            if (attempt > 1) {
                console.error('Lỗi khi thêm vào giỏ hàng:', err);
                form.submit();
            }
        });
    }

    chatBody.addEventListener('submit', function (event) {
        const form = event.target.closest('.chatbot-product-card__form');
        if (!form) return;

        event.preventDefault();
        event.stopPropagation();

        const btn = form.querySelector('.chatbot-product-card__btn');
        if (!btn || btn.disabled) return;

        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = form.action.includes('buy-now') ? 'Đang đặt...' : 'Đang thêm...';

        submitCartForm(form, btn, originalText);
    });

    restoreHistory();
    bindSuggestionChips();
}
