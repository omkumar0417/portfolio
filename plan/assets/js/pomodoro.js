/**
 * Pomodoro Focus timer script
 */

let timeLeft = 25 * 60; // 25 minutes default
let timerInterval = null;
let isPaused = true;
let currentMode = "focus"; // focus, short_break, long_break
let modeDurationMinutes = 25;

document.addEventListener("DOMContentLoaded", function() {
    const startBtn = document.getElementById("timer-start-btn");
    const pauseBtn = document.getElementById("timer-pause-btn");
    const resetBtn = document.getElementById("timer-reset-btn");

    if (startBtn && pauseBtn && resetBtn) {
        startBtn.addEventListener("click", startTimer);
        pauseBtn.addEventListener("click", pauseTimer);
        resetBtn.addEventListener("click", resetTimer);
    }
});

/**
 * Configure active mode duration rules
 */
function setTimerMode(mode, minutes) {
    currentMode = mode;
    modeDurationMinutes = minutes;
    timeLeft = minutes * 60;
    isPaused = true;
    
    clearInterval(timerInterval);
    timerInterval = null;
    
    updateTimerDisplay();

    // Toggle active classes on tabs
    const modes = ["focus", "short", "long"];
    modes.forEach(m => {
        const btn = document.getElementById(`mode-${m}`);
        if (btn) btn.classList.remove("active");
    });
    
    const activeBtn = document.getElementById(`mode-${mode === 'focus' ? 'focus' : (mode === 'short_break' ? 'short' : 'long')}`);
    if (activeBtn) activeBtn.classList.add("active");

    // Toggle play state button controls
    toggleStartPauseUI(true);
}

function startTimer() {
    if (timerInterval !== null) return;
    
    isPaused = false;
    toggleStartPauseUI(false);
    
    timerInterval = setInterval(() => {
        if (timeLeft > 0) {
            timeLeft--;
            updateTimerDisplay();
        } else {
            // Completed!
            sessionCompleteTrigger();
        }
    }, 1000);
}

function pauseTimer() {
    isPaused = true;
    clearInterval(timerInterval);
    timerInterval = null;
    toggleStartPauseUI(true);
}

function resetTimer() {
    pauseTimer();
    timeLeft = modeDurationMinutes * 60;
    updateTimerDisplay();
}

function toggleStartPauseUI(showStart) {
    const startBtn = document.getElementById("timer-start-btn");
    const pauseBtn = document.getElementById("timer-pause-btn");
    
    if (startBtn && pauseBtn) {
        if (showStart) {
            startBtn.classList.remove("d-none");
            pauseBtn.classList.add("d-none");
        } else {
            startBtn.classList.add("d-none");
            pauseBtn.classList.remove("d-none");
        }
    }
}

function updateTimerDisplay() {
    const display = document.getElementById("timer-display");
    if (!display) return;

    const mins = Math.floor(timeLeft / 60);
    const secs = timeLeft % 60;
    const timeStr = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    
    display.textContent = timeStr;
    
    // Also update browser tab title
    document.title = `(${timeStr}) ${currentMode === 'focus' ? 'Focus Block' : 'Break Time'} | AetherLife`;
}

/**
 * Handle focus block complete events
 */
function sessionCompleteTrigger() {
    pauseTimer();
    playAlarmSynthBeep();

    const taskIdSelect = document.getElementById("link-task-select");
    const taskId = taskIdSelect ? taskIdSelect.value : "";

    const formData = new FormData();
    formData.append("action", "log");
    formData.append("duration_minutes", modeDurationMinutes.toString());
    formData.append("task_id", taskId);
    formData.append("type", currentMode);
    formData.append("csrf_token", CSRF_TOKEN);

    // Save session to database logs via AJAX POST
    fetch(`${APP_URL}/pomodoro`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: currentMode === 'focus' ? "Focus Block Done!" : "Break Completed!",
                text: currentMode === 'focus' ? "You earned focus minutes points! Take a short break now." : "Get ready to focus again.",
                icon: "success",
                confirmButtonText: "Done",
                confirmButtonColor: "var(--clr-accent)"
            }).then(() => {
                // Auto transition next mode if checked
                const autoStart = document.getElementById("auto-start-next");
                if (autoStart && autoStart.checked) {
                    if (currentMode === "focus") {
                        setTimerMode("short_break", 5);
                        startTimer();
                    } else {
                        setTimerMode("focus", 25);
                        startTimer();
                    }
                } else {
                    window.location.reload(); // Reload details to show list updates
                }
            });
        }
    })
    .catch(err => console.error("Pomodoro log save failed: ", err));
}

/**
 * Synthesize standard clean double-beeps utilizing browser Web Audio API
 */
function playAlarmSynthBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        
        // First beep
        triggerBeep(audioCtx, 880, 0, 0.4); // A5 note
        // Second beep
        triggerBeep(audioCtx, 880, 0.5, 0.4);
    } catch (e) {
        console.error("Web Audio Context blocked or unsupported: ", e);
    }
}

function triggerBeep(audioCtx, frequency, startTimeOffset, duration) {
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    
    osc.type = "sine";
    osc.frequency.setValueAtTime(frequency, audioCtx.currentTime + startTimeOffset);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    
    // Smooth gain fades
    gain.gain.setValueAtTime(0, audioCtx.currentTime + startTimeOffset);
    gain.gain.linearRampToValueAtTime(0.4, audioCtx.currentTime + startTimeOffset + 0.05);
    gain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + startTimeOffset + duration);
    
    osc.start(audioCtx.currentTime + startTimeOffset);
    osc.stop(audioCtx.currentTime + startTimeOffset + duration + 0.1);
}
