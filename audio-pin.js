class AudioPinManager {
    constructor() {
        this.audioContext = null; // Initialize as null first
        this.activeAudioNodes = new Map();
        this.AUDIO_RADIUS = 200; // pixels
        this.volumeCurve = this.createVolumeCurve();
        this.currentUserPosition = { x: 0, y: 0 };
        this.audioEnabled = false;
        this.proxyUrl = 'proxy_audio.php'; // Default proxy URL
        console.log("AudioPinManager initialized");
        
        // Create audio enable button
        this.createAudioEnableButton();
    }

    createAudioEnableButton() {
        const button = document.createElement('button');
        button.textContent = '🔇 Enable Audio';
        button.style.position = 'fixed';
        button.style.top = '20px';
        button.style.right = '20px';
        button.style.padding = '10px 15px';
        button.style.borderRadius = '5px';
        button.style.border = '2px solid black';
        button.style.background = 'white';
        button.style.cursor = 'pointer';
        button.style.zIndex = '1000';
        button.style.fontFamily = 'Inter, sans-serif';
        button.style.fontSize = '14px';
        
        button.addEventListener('click', () => {
            this.enableAudio();
            button.textContent = '🔊 Audio Enabled';
            button.style.background = '#e0e0e0';
            button.disabled = true;
        });
        
        document.body.appendChild(button);
        this.audioEnableButton = button;
    }

    createVolumeCurve() {
        const curve = new Float32Array(128);
        for (let i = 0; i < 128; i++) {
            const distance = (i / 127) * this.AUDIO_RADIUS;
            curve[i] = Math.max(0, 1 - (distance / this.AUDIO_RADIUS));
        }
        return curve;
    }

    createVisualIndicator(pin) {
        const indicator = document.createElement('div');
        indicator.className = 'audio-indicator';
        indicator.style.cssText = `
            position: absolute;
            left: ${pin.x}px;
            top: ${pin.y}px;
            width: 20px;
            height: 20px;
            background: black;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
            z-index: 1000;
            cursor: pointer;
        `;
        
        // Add title tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'audio-tooltip';
        tooltip.textContent = pin.title;
        tooltip.style.cssText = `
            position: absolute;
            left: 50%;
            top: -25px;
            transform: translateX(-50%);
            background: black;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        `;
        
        indicator.appendChild(tooltip);
        document.body.appendChild(indicator);
        
        // Show tooltip on hover
        indicator.addEventListener('mouseenter', () => {
            tooltip.style.opacity = '1';
        });
        indicator.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
        });
        
        // Add click event to navigate to pin
        indicator.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            this.navigateToPin(pin);
        });
        
        return indicator;
    }
    
    navigateToPin(pin) {
        // Get the play area element
        const playArea = document.getElementById('play-area');
        if (!playArea) return;
        
        // Calculate the center position
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Calculate scroll position to center the pin
        const scrollX = pin.x - (viewportWidth / 2);
        const scrollY = pin.y - (viewportHeight / 2);
        
        // Smooth scroll to the pin
        playArea.scrollTo({
            left: scrollX,
            top: scrollY,
            behavior: 'smooth'
        });
        
        // Update user position to the pin's position
        this.currentUserPosition = { x: pin.x, y: pin.y };
        
        // Update audio positions based on new user position
        this.updateAudioPositions(this.currentUserPosition.x, this.currentUserPosition.y);
        
        // Highlight the pin briefly
        const indicator = this.activeAudioNodes.get(pin.id)?.indicator;
        if (indicator) {
            // Add a highlight effect
            indicator.style.boxShadow = '0 0 0 4px rgba(255, 255, 0, 0.7)';
            
            // Remove the highlight after a delay
            setTimeout(() => {
                indicator.style.boxShadow = 'none';
            }, 1500);
        }
    }

    async createAudioPin(x, y, audioUrl, title) {
        console.log("Creating audio pin:", { x, y, audioUrl, title });
        
        // Initialize audio context if not already done
        if (!this.audioContext) {
            this.initAudioContext();
        }
        
        const pin = {
            id: Date.now().toString(),
            x,
            y,
            audioUrl,
            title,
            type: 'audio'
        };

        // Create audio element with CORS handling
        const audio = new Audio();
        
        // Set CORS attributes
        audio.crossOrigin = "anonymous";
        
        // Use proxy if the URL is from a different domain
        const finalAudioUrl = this.shouldUseProxy(audioUrl) ? 
            `${this.proxyUrl}?url=${encodeURIComponent(audioUrl)}` : 
            audioUrl;
        
        audio.src = finalAudioUrl;
        audio.loop = true;
        
        // Add error handling for audio loading
        audio.addEventListener('error', (e) => {
            console.error("Audio loading error:", e);
            this.showAudioError(pin, "Error loading audio. CORS issue or invalid URL.");
        });
        
        // Add loaded event to confirm audio is ready
        audio.addEventListener('loadeddata', () => {
            console.log("Audio loaded successfully:", audioUrl);
        });

        try {
            // Create audio node
            const source = this.audioContext.createMediaElementSource(audio);
            const gainNode = this.audioContext.createGain();
            gainNode.gain.value = 0;

            // Connect nodes
            source.connect(gainNode);
            gainNode.connect(this.audioContext.destination);

            // Create visual indicator
            const indicator = this.createVisualIndicator(pin);

            // Store audio nodes and visual elements
            this.activeAudioNodes.set(pin.id, {
                audio,
                source,
                gainNode,
                pin,
                indicator
            });
            
            console.log("Audio pin created successfully:", pin.id);
            return pin;
        } catch (error) {
            console.error("Error creating audio pin:", error);
            this.showAudioError(pin, "Error creating audio source. CORS issue or invalid URL.");
            throw error;
        }
    }

    // Check if we should use a proxy for this URL
    shouldUseProxy(url) {
        try {
            const urlObj = new URL(url);
            const currentUrl = window.location.href;
            const currentUrlObj = new URL(currentUrl);
            
            // Use proxy if the domains don't match
            return urlObj.hostname !== currentUrlObj.hostname;
        } catch (e) {
            console.error("Error parsing URL:", e);
            return true; // Use proxy if URL parsing fails
        }
    }

    // Show error message for audio pin
    showAudioError(pin, message) {
        const indicator = this.activeAudioNodes.get(pin.id)?.indicator;
        if (indicator) {
            // Add error styling
            indicator.style.background = 'red';
            indicator.style.boxShadow = '0 0 0 2px rgba(255, 0, 0, 0.5)';
            
            // Add error tooltip
            const tooltip = indicator.querySelector('.audio-tooltip');
            if (tooltip) {
                tooltip.textContent = message;
                tooltip.style.background = 'red';
                tooltip.style.opacity = '1';
            }
        }
    }

    updateAudioPositions(cursorX, cursorY) {
        // Update current user position
        this.currentUserPosition = { x: cursorX, y: cursorY };
        
        // Check if audio context is initialized
        if (!this.audioContext || !this.audioEnabled) {
            return;
        }
        
        this.activeAudioNodes.forEach(({ audio, gainNode, pin, indicator }) => {
            const distance = Math.sqrt(
                Math.pow(cursorX - pin.x, 2) + 
                Math.pow(cursorY - pin.y, 2)
            );

            if (distance <= this.AUDIO_RADIUS) {
                if (audio.paused) {
                    console.log("Playing audio for pin:", pin.id, "at distance:", distance);
                    audio.play().catch(error => {
                        console.error("Error playing audio:", error);
                    });
                }
                const volume = Math.max(0, 1 - (distance / this.AUDIO_RADIUS));
                gainNode.gain.value = volume;
                
                // Update visual indicator
                const scale = 1 + (volume * 0.5); // Scale up to 1.5x based on volume
                indicator.style.transform = `translate(-50%, -50%) scale(${scale})`;
                indicator.style.background = `rgba(0, 0, 0, ${0.3 + (volume * 0.7)})`; // Opacity based on volume
                
                // Add pulsing animation
                indicator.style.animation = 'pulse 1s infinite';
            } else {
                if (!audio.paused) {
                    console.log("Pausing audio for pin:", pin.id, "at distance:", distance);
                    audio.pause();
                }
                gainNode.gain.value = 0;
                
                // Reset visual indicator
                indicator.style.transform = 'translate(-50%, -50%) scale(1)';
                indicator.style.background = 'rgba(0, 0, 0, 0.3)';
                indicator.style.animation = 'none';
            }
        });
    }

    removeAudioPin(pinId) {
        const audioData = this.activeAudioNodes.get(pinId);
        if (audioData) {
            audioData.audio.pause();
            audioData.source.disconnect();
            audioData.indicator.remove();
            this.activeAudioNodes.delete(pinId);
        }
    }

    // Initialize audio context on user interaction
    initAudioContext() {
        console.log("Initializing audio context");
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            console.log("Audio context created:", this.audioContext.state);
            
            if (this.audioContext.state === 'suspended') {
                console.log("Resuming audio context");
                this.audioContext.resume().then(() => {
                    console.log("Audio context resumed:", this.audioContext.state);
                });
            }
        } catch (error) {
            console.error("Error initializing audio context:", error);
        }
    }
    
    // Enable audio with user interaction
    enableAudio() {
        console.log("Enabling audio");
        this.audioEnabled = true;
        
        // Initialize audio context if not already done
        if (!this.audioContext) {
            this.initAudioContext();
        }
        
        // Resume audio context if suspended
        if (this.audioContext && this.audioContext.state === 'suspended') {
            this.audioContext.resume().then(() => {
                console.log("Audio context resumed:", this.audioContext.state);
                
                // Play a silent sound to unlock audio
                const oscillator = this.audioContext.createOscillator();
                const gainNode = this.audioContext.createGain();
                gainNode.gain.value = 0.01; // Very quiet
                oscillator.connect(gainNode);
                gainNode.connect(this.audioContext.destination);
                oscillator.start();
                oscillator.stop(this.audioContext.currentTime + 0.1);
                
                // Update audio positions to start playing audio
                this.updateAudioPositions(this.currentUserPosition.x, this.currentUserPosition.y);
            });
        }
    }
}

// Add pulse animation to document
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: translate(-50%, -50%) scale(1); }
        50% { transform: translate(-50%, -50%) scale(1.2); }
        100% { transform: translate(-50%, -50%) scale(1); }
    }
`;
document.head.appendChild(style);

// Export the class
window.AudioPinManager = AudioPinManager; 