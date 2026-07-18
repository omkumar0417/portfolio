<?php
/**
 * Daily Journal View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();
?>
<div class="row g-4">
    <!-- 1. Journal History list sidebar -->
    <div class="col-lg-3" data-aos="fade-right">
        <div class="card glass-panel h-100 p-3">
            <h6 class="text-white fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Entries</h6>
            
            <div class="list-group list-group-flush mb-3">
                <?php if (empty($history)): ?>
                    <p class="text-muted small text-center my-3">No history entries logged.</p>
                <?php else: ?>
                    <?php foreach ($history as $h): 
                        $moodIcons = [
                            'happy' => '😊',
                            'energetic' => '⚡',
                            'neutral' => '😐',
                            'tired' => '😴',
                            'sad' => '😢',
                            'anxious' => '😰'
                        ];
                        $icon = $moodIcons[$h['mood']] ?? '📝';
                    ?>
                        <a href="<?= e(APP_URL) ?>/journal?date=<?= $h['date'] ?>" class="list-group-item bg-transparent text-white border-0 py-2 px-1 small d-flex justify-content-between align-items-center <?= $h['date'] === $date ? 'fw-bold text-primary' : 'text-muted' ?>">
                            <span><i class="fa-regular fa-calendar-check me-2"></i> <?= date('d M Y', strtotime($h['date'])) ?></span>
                            <span><?= $icon ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Date Select picker -->
            <div class="border-top border-color pt-3">
                <label for="journal-date-picker" class="form-label text-muted small">Go to specific date</label>
                <input type="date" id="journal-date-picker" class="form-control bg-transparent text-white border-secondary small" value="<?= e($date) ?>" onchange="window.location.href = APP_URL + '/journal?date=' + this.value">
            </div>
        </div>
    </div>

    <!-- 2. Structured Check-in Forms -->
    <div class="col-lg-9" data-aos="fade-left">
        <div class="card glass-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-color pb-2 flex-wrap gap-2">
                <h4 class="text-white fw-bold mb-0">Daily Log: <?= date('d F Y', strtotime($date)) ?></h4>
                <div class="d-flex gap-2">
                    <a href="<?= e(APP_URL) ?>/journal?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Go to Today</a>
                </div>
            </div>

            <form action="<?= e(APP_URL) ?>/journal?date=<?= e($date) ?>" method="POST">
                <?php csrfInput(); ?>
                
                <div class="row g-4">
                    <!-- Morning Prompt section -->
                    <div class="col-md-6 border-end border-color pe-md-4">
                        <h5 class="text-primary fw-bold mb-3"><i class="fa-regular fa-sun me-2"></i>Morning Check-In</h5>
                        
                        <div class="mb-3">
                            <label for="morning_journal" class="form-label text-white small">Morning Reflections / Goals for today</label>
                            <textarea name="morning_journal" id="morning_journal" rows="3" class="form-control bg-transparent text-white border-secondary small" placeholder="How did you sleep? What are you focusing on today?"><?= e($entry['morning_journal'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gratitude" class="form-label text-white small">Gratitude (3 things you are grateful for)</label>
                            <textarea name="gratitude" id="gratitude" rows="3" class="form-control bg-transparent text-white border-secondary small" placeholder="1. My health&#10;2. A clean workspace&#10;3. Coffee"><?= e($entry['gratitude'] ?? '') ?></textarea>
                        </div>

                        <!-- Mood Picker radios -->
                        <div class="mb-3">
                            <label class="form-label text-white small d-block">Current Mood / Vibe</label>
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <?php 
                                    $moodsMap = [
                                        'happy' => '😊 Happy',
                                        'energetic' => '⚡ Energetic',
                                        'neutral' => '😐 Neutral',
                                        'tired' => '😴 Tired',
                                        'sad' => '😢 Sad',
                                        'anxious' => '😰 Anxious'
                                    ];
                                    $selectedMood = $entry['mood'] ?? 'neutral';
                                    foreach ($moodsMap as $key => $label):
                                        $active = $key === $selectedMood ? 'active btn-primary' : 'btn-outline-secondary';
                                ?>
                                    <input type="radio" class="btn-check" name="mood" id="mood-<?= $key ?>" value="<?= $key ?>" <?= $key === $selectedMood ? 'checked' : '' ?>>
                                    <label class="btn btn-sm <?= $active ?> px-2 py-1" for="mood-<?= $key ?>"><?= $label ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="energy_level" class="form-label text-white small">Energy Level (1 - Low, 5 - High)</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="range" name="energy_level" id="energy_level" class="form-range" min="1" max="5" value="<?= (int)($entry['energy_level'] ?? 3) ?>">
                                <span class="text-white fw-bold fs-5" id="energy-val-display"><?= (int)($entry['energy_level'] ?? 3) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Night Review section -->
                    <div class="col-md-6 ps-md-4">
                        <h5 class="text-warning fw-bold mb-3"><i class="fa-regular fa-moon me-2"></i>Night Review</h5>
                        
                        <div class="mb-3">
                            <label for="night_journal" class="form-label text-white small">Night Journal / Evening Summary</label>
                            <textarea name="night_journal" id="night_journal" rows="3" class="form-control bg-transparent text-white border-secondary small" placeholder="Summarize your afternoon and evening..."><?= e($entry['night_journal'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="achievements" class="form-label text-white small">Achievements / Daily Wins</label>
                            <textarea name="achievements" id="achievements" rows="2" class="form-control bg-transparent text-white border-secondary small" placeholder="What tasks did you complete successfully?"><?= e($entry['achievements'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="learning" class="form-label text-white small">Today's Key Learning</label>
                            <textarea name="learning" id="learning" rows="2" class="form-control bg-transparent text-white border-secondary small" placeholder="Lessons, code snippets, interesting facts..."><?= e($entry['learning'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="productivity_score" class="form-label text-white small">Self Productivity Rating (1-5)</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="range" name="productivity_score" id="productivity_score" class="form-range" min="1" max="5" value="<?= (int)($entry['productivity_score'] ?? 3) ?>">
                                <span class="text-white fw-bold fs-5" id="prod-val-display"><?= (int)($entry['productivity_score'] ?? 3) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-color text-end">
                    <button type="submit" class="btn btn-accent px-4"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Save Journal Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Slider values updates indicators
    document.addEventListener("DOMContentLoaded", function() {
        const energySlider = document.getElementById("energy_level");
        const energyDisplay = document.getElementById("energy-val-display");
        
        if (energySlider && energyDisplay) {
            energySlider.addEventListener("input", function() {
                energyDisplay.textContent = this.value;
            });
        }
        
        const prodSlider = document.getElementById("productivity_score");
        const prodDisplay = document.getElementById("prod-val-display");
        
        if (prodSlider && prodDisplay) {
            prodSlider.addEventListener("input", function() {
                prodDisplay.textContent = this.value;
            });
        }

        // Handle Active states on button radios clicking
        const radios = document.querySelectorAll('input[name="mood"]');
        radios.forEach(r => {
            r.addEventListener("change", function() {
                // Clear active states on all labels
                radios.forEach(x => {
                    const label = document.querySelector(`label[for="${x.id}"]`);
                    label.className = "btn btn-sm btn-outline-secondary px-2 py-1";
                });
                // Set active class
                const activeLabel = document.querySelector(`label[for="${this.id}"]`);
                activeLabel.className = "btn btn-sm btn-primary active px-2 py-1";
            });
        });
    });
</script>
