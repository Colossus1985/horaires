let pendingSaves = 0; // Compteur de sauvegardes en cours

function formatHoursToHM(decimalHours) {
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    return `${hours}h${minutes.toString().padStart(2, "0")}`;
}

function calculateTimeDiff(startTime, endTime, breakMinutes = 0) {
    if (!startTime || !endTime) return 0;
    
    // Cas spécial: 00:00-00:00 = pas d'horaires de base (weekend)
    if (startTime === "00:00" && endTime === "00:00") return 0;

    const [startHour, startMin] = startTime.split(":").map(Number);
    const [endHour, endMin] = endTime.split(":").map(Number);

    let start = startHour * 60 + startMin;
    let end = endHour * 60 + endMin;

    if (end < start) end += 24 * 60;

    return (end - start - breakMinutes) / 60;
}

function updateDayDisplay(dayCell) {
    const startTime = dayCell.querySelector(".start-time").value;
    const endTime = dayCell.querySelector(".end-time").value;
    const breakDuration =
        parseInt(dayCell.querySelector(".break-duration").value) || 0;
    const display = dayCell.querySelector(".overtime-display");
    const recoveredSelect = dayCell.querySelector(".recovered-hours");
    const excludeCheckbox = dayCell.querySelector(".exclude-balance");

    // Récupérer les horaires de base pour ce jour
    const defaultStart = dayCell.dataset.defaultStart;
    const defaultEnd = dayCell.dataset.defaultEnd;
    const defaultBreak = parseInt(dayCell.dataset.defaultBreak) || 0;

    // Heures travaillées effectives (avec pause déduite)
    const workedHours = calculateTimeDiff(startTime, endTime, breakDuration);
    // Heures de base effectives (avec pause de base déduite)
    const baseHours = calculateTimeDiff(defaultStart, defaultEnd, defaultBreak);
    // Heures supplémentaires = différence entre heures travaillées et heures de base (peut être négatif)
    const overtimeHours = workedHours - baseHours;

    // Sauvegarder la valeur actuelle avant de reconstruire le select
    const currentRecovered = parseFloat(recoveredSelect.value) || 0;

    // Mettre à jour les options de récupération en fonction des heures supp disponibles
    const maxRecoverable =
        overtimeHours > 0 ? Math.ceil(overtimeHours / 0.25) * 0.25 : 0;

    // Ne reconstruire que si le max a changé
    const lastMax = parseFloat(recoveredSelect.dataset.lastMax || "0");
    if (lastMax !== maxRecoverable) {
        recoveredSelect.innerHTML = "";

        for (let i = 0; i <= maxRecoverable * 4 && i <= 48; i++) {
            const hours = i * 0.25;
            const option = document.createElement("option");
            option.value = hours;
            option.textContent = formatHoursToHM(hours);
            recoveredSelect.appendChild(option);
        }

        recoveredSelect.dataset.lastMax = maxRecoverable;

        // Restaurer la valeur si elle est toujours valide, sinon mettre au max
        if (currentRecovered <= maxRecoverable) {
            recoveredSelect.value = currentRecovered;
        } else {
            recoveredSelect.value = maxRecoverable;
        }
    }

    // Gestion des classes visuelles
    dayCell.classList.remove("has-overtime", "has-negative", "fully-recovered");

    // Vérifier si c'est un weekend ou jour férié
    const isWeekendOrHoliday =
        dayCell.classList.contains("weekend") ||
        dayCell.classList.contains("holiday");

    if (overtimeHours > 0) {
        dayCell.classList.add("has-overtime");
        const recoveredHours = parseFloat(recoveredSelect.value) || 0;
        if (recoveredHours >= overtimeHours) {
            dayCell.classList.add("fully-recovered");
        }
    } else if (overtimeHours < 0 && !isWeekendOrHoliday) {
        // Ne marquer comme négatif que si ce n'est pas un weekend ou jour férié
        dayCell.classList.add("has-negative");
    }

    // Affichage des heures
    if (startTime && endTime) {
        if (overtimeHours > 0) {
            display.innerHTML = `<div style="color: #2ecc71; font-weight: bold;">+${formatHoursToHM(
                overtimeHours
            )}</div>`;
        } else if (overtimeHours < 0) {
            display.innerHTML = `<div style="color: #e74c3c; font-weight: bold;">-${formatHoursToHM(
                Math.abs(overtimeHours)
            )}</div>`;
        } else {
            display.innerHTML = "";
        }
    } else {
        display.innerHTML = "";
    }

    updateTotals();
}

function updateTotals() {
    let totalWorked = 0;
    let totalOvertime = 0;
    let totalMissing = 0;
    let totalRecovered = 0;

    document.querySelectorAll(".day-cell[data-date]").forEach((cell) => {
        const startTime = cell.querySelector(".start-time").value;
        const endTime = cell.querySelector(".end-time").value;
        const breakDuration =
            parseInt(cell.querySelector(".break-duration").value) || 0;
        const recoveredHours =
            parseFloat(cell.querySelector(".recovered-hours").value) || 0;
        const excludeFromBalance =
            cell.querySelector(".exclude-balance").checked;

        // Récupérer les horaires de base spécifiques à ce jour
        const defaultStart = cell.dataset.defaultStart;
        const defaultEnd = cell.dataset.defaultEnd;
        const defaultBreak = parseInt(cell.dataset.defaultBreak) || 0;

        if (startTime && endTime) {
            const workedHours = calculateTimeDiff(
                startTime,
                endTime,
                breakDuration
            );
            const baseHours = calculateTimeDiff(
                defaultStart,
                defaultEnd,
                defaultBreak
            );
            const overtimeHours = workedHours - baseHours; // Peut être négatif

            totalWorked += workedHours;

            // Compter les heures supp/négatives seulement si pas exclu du solde
            if (!excludeFromBalance) {
                if (overtimeHours > 0) {
                    totalOvertime += overtimeHours;
                } else if (overtimeHours < 0 && recoveredHours === 0) {
                    // Ne compter les heures manquantes que si pas de récupération saisie
                    totalMissing += Math.abs(overtimeHours);
                }
                totalRecovered += recoveredHours;
            }
        }
    });

    const balance = totalOvertime - totalMissing - totalRecovered;

    document.getElementById("total-worked").textContent =
        formatHoursToHM(totalWorked);
    document.getElementById("total-overtime").textContent =
        formatHoursToHM(totalOvertime);
    document.getElementById("total-missing").textContent =
        formatHoursToHM(totalMissing);
    document.getElementById("total-recovered").textContent =
        formatHoursToHM(totalRecovered);
    document.getElementById("total-balance").textContent = formatHoursToHM(
        Math.abs(balance)
    );
    document.getElementById("total-balance").style.color =
        balance >= 0 ? "#2ecc71" : "#e74c3c";
}

function saveDay(dayCell, saveUrl, csrfToken) {
    const date = dayCell.dataset.date;
    const startTime = dayCell.querySelector(".start-time").value;
    const endTime = dayCell.querySelector(".end-time").value;
    const breakDuration = dayCell.querySelector(".break-duration").value;
    const recoveredHours = dayCell.querySelector(".recovered-hours").value;
    const reason = dayCell.querySelector(".reason-input").value;
    const excludeFromBalance =
        dayCell.querySelector(".exclude-balance").checked;

    // Récupérer les horaires de base pour ce jour spécifique
    const baseStartTime = dayCell.dataset.defaultStart;
    const baseEndTime = dayCell.dataset.defaultEnd;

    pendingSaves++;

    fetch(saveUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({
            date: date,
            start_time: startTime,
            end_time: endTime,
            base_start_time: baseStartTime,
            base_end_time: baseEndTime,
            break_duration: breakDuration,
            recovered_hours: recoveredHours,
            reason: reason,
            exclude_from_balance: excludeFromBalance,
            rate: 15,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Marquer la cellule comme sauvegardée pour éviter l'écrasement
                dayCell.setAttribute("data-saved", "true");
                updateDayDisplay(dayCell);
            }
        })
        .finally(() => {
            pendingSaves--;
        });
}

function initCalendar(saveUrl, setBaseHoursUrl, csrfToken) {
    // Écouter les changements sur tous les inputs
    document.querySelectorAll(".day-cell[data-date]").forEach((cell) => {
        const inputs = cell.querySelectorAll("select, input");
        const startInput = cell.querySelector(".start-time");
        const endInput = cell.querySelector(".end-time");
        const breakInput = cell.querySelector(".break-duration");

        // Pré-remplir les jours ouvrés qui n'ont PAS été sauvegardés manuellement
        const isWeekendOrHoliday =
            cell.classList.contains("weekend") ||
            cell.classList.contains("holiday");
        const wasManuallySaved = cell.hasAttribute("data-saved");

        if (!isWeekendOrHoliday && !wasManuallySaved) {
            startInput.value = startInput.dataset.default;
            endInput.value = endInput.dataset.default;
            breakInput.value = breakInput.dataset.default;
        }

        // Mettre à jour l'affichage pour cette cellule
        updateDayDisplay(cell);

        inputs.forEach((input) => {
            input.addEventListener("change", function () {
                updateDayDisplay(cell);
                saveDay(cell, saveUrl, csrfToken);
            });
        });
    });

    // Calcul initial
    updateTotals();

    // Gérer la soumission du formulaire des horaires de base en AJAX
    document
        .getElementById("base-hours-form")
        .addEventListener("submit", async function (e) {
            e.preventDefault();

            // Attendre que toutes les sauvegardes en cours soient terminées
            while (pendingSaves > 0) {
                await new Promise((resolve) => setTimeout(resolve, 100));
            }

            const formData = new FormData(this);

            fetch(setBaseHoursUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    // Recharger la page pour appliquer les nouveaux horaires de base
                    window.location.reload();
                })
                .catch((error) => {
                    console.error("Erreur:", error);
                    alert("Erreur lors de la sauvegarde des horaires de base");
                });
        });
}
