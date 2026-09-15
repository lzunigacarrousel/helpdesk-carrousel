(() => {
  document.querySelectorAll('.agenda-event[data-start-slot][data-span-slots]').forEach((event) => {
    const start = Number.parseInt(event.dataset.startSlot || '0', 10);
    const span = Math.max(1, Number.parseInt(event.dataset.spanSlots || '1', 10));
    if (Number.isFinite(start)) event.style.setProperty('--agenda-start-slot', String(start));
    event.style.setProperty('--agenda-span-slots', String(span));
  });
})();