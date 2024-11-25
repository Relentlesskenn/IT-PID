<?php
/**
 * Advertisement Component for IT-PID Budget Tracking App
 * Handles rendering of advertisement placeholders throughout the application
 * 
 * @package IT-PID
 * @version 1.0.0
 */

class Advertisement {
    /**
     * Default styles applied to all ad containers
     * @var array
     */
    private static $defaultStyles = [
        'maxWidth' => '1500px',
        'height' => '100px',
        'mobileHeight' => '80px'
    ];

    /**
     * Renders a centered advertisement placeholder that matches the app's design
     * @param string $position Optional. Specifies where the ad is being displayed (for tracking)
     * @return string HTML markup for the advertisement
     */
    public static function render($position = 'default') {
        // Generate unique identifier for this ad instance
        $uniqueId = 'ad-' . uniqid();
        
        // Build the HTML structure
        return '
        <div class="advertisement-wrapper my-4" data-ad-position="' . htmlspecialchars($position) . '">
            <div class="container d-flex justify-content-center">
                <div class="advertisement-container">
                    <div class="ad-label">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Advertisement
                    </div>
                    <div id="' . $uniqueId . '" class="ad-content">
                        <div class="placeholder-stripes">
                            <div class="placeholder-content">
                                <div class="sample-text">Sample Advertisement</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ' . self::getStyles() . '
        </div>';
    }

    /**
     * Generates the CSS styles for the advertisement
     * @return string CSS styles wrapped in style tags 
     */
    private static function getStyles() {
        return '<style>
            .advertisement-wrapper {
                width: 100%;
                padding: 0;
                margin: 0; /* Remove default margins */
            }
            
            .advertisement-container {
                width: 100%; /* Full width */
                max-width: ' . self::$defaultStyles['maxWidth'] . ';
                background: #FFFFFF;
                border-radius: 13px;
                overflow: hidden;
                box-shadow: none; /* Remove shadow to match other cards */
                transition: box-shadow 0.3s ease;
            }
            
            .advertisement-wrapper .container {
                padding: 0;
            }    
            
            .advertisement-container:hover {
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            .ad-label {
                font-size: 0.75rem;
                color: #6c757d;
                padding: 0.5rem;
                text-align: center;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                user-select: none;
            }
            
            .ad-content {
                width: 100%;
                height: ' . self::$defaultStyles['height'] . ';
                position: relative;
                background: #ffffff;
            }
            
            .placeholder-stripes {
                width: 100%;
                height: 100%;
                background: repeating-linear-gradient(
                    45deg,
                    #f8f9fa,
                    #f8f9fa 10px,
                    #ffffff 10px,
                    #ffffff 20px
                );
                display: flex;
                align-items: center;
                justify-content: center;
                animation: slide 20s linear infinite;
            }
            
            @keyframes slide {
                from {
                    background-position: 0 0;
                }
                to {
                    background-position: 40px 40px;
                }
            }
            
            .placeholder-content {
                background: rgba(255, 255, 255, 0.9);
                padding: 0.5rem 1rem;
                border-radius: 4px;
                backdrop-filter: blur(4px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .sample-text {
                color: #6c757d;
                font-size: 0.875rem;
                font-weight: 500;
            }
            
            @media (max-width: 768px) {
                .advertisement-container {
                    width: 100%;
                }
                
                .ad-content {
                    height: ' . self::$defaultStyles['mobileHeight'] . ';
                }
                
                .sample-text {
                    font-size: 0.8rem;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .placeholder-stripes {
                    animation: none;
                }
                
                .advertisement-container {
                    transition: none;
                }
            }

            @media print {
                .advertisement-wrapper {
                    display: none;
                }
            }
        </style>';
    }

    /**
     * Sets custom dimensions for the advertisement
     * @param array $dimensions Array containing width and height values
     * @return void
     */
    public static function setDimensions($dimensions = []) {
        if (isset($dimensions['maxWidth'])) {
            self::$defaultStyles['maxWidth'] = $dimensions['maxWidth'];
        }
        if (isset($dimensions['height'])) {
            self::$defaultStyles['height'] = $dimensions['height'];
        }
        if (isset($dimensions['mobileHeight'])) {
            self::$defaultStyles['mobileHeight'] = $dimensions['mobileHeight'];
        }
    }

    /**
     * Renders a small advertisement suitable for sidebar or narrow spaces
     * @return string HTML markup for the small advertisement
     */
    public static function renderSmall() {
        // Temporarily adjust dimensions
        $originalDimensions = self::$defaultStyles;
        self::setDimensions([
            'maxWidth' => '300px',
            'height' => '80px',
            'mobileHeight' => '60px'
        ]);
        
        $output = self::render('sidebar');
        
        // Restore original dimensions
        self::$defaultStyles = $originalDimensions;
        
        return $output;
    }

    /**
     * Checks if ad blocking is potentially active
     * Note: This is a basic check and may not catch all ad blockers
     * @return bool True if ad blocking is suspected
     */
    public static function isAdBlockerActive() {
        return '<script>
            function checkAdBlocker() {
                return new Promise((resolve) => {
                    let adBlockEnabled = false;
                    const testAd = document.createElement("div");
                    testAd.innerHTML = "&nbsp;";
                    testAd.className = "adsbox";
                    document.body.appendChild(testAd);
                    window.setTimeout(() => {
                        if (testAd.offsetHeight === 0) {
                            adBlockEnabled = true;
                        }
                        testAd.remove();
                        resolve(adBlockEnabled);
                    }, 100);
                });
            }
        </script>';
    }
}
?>