(() => {
  document.querySelectorAll('.agenda-event[data-start-slot][data-span-slots]').forEach((event) => {
    const start = Number.parseInt(event.dataset.startSlot || '0', 10);
    const span = Math.max(1, Number.parseInt(event.dataset.spanSlots || '1', 10));
    if (Number.isFinite(start)) event.style.setProperty('--agenda-start-slot', String(start));
    event.style.setProperty('--agenda-span-slots', String(span));
  });

  const shell = document.querySelector('.agenda-shell');
  const quickLinks = document.querySelector('.agenda-quick-links');
  if (!shell || !quickLinks || quickLinks.querySelector('[data-agenda-date-jump]')) return;

  const isMonth = shell.classList.contains('agenda-view-month');
  const isWeek = shell.classList.contains('agenda-view-calendar');
  if (!isMonth && !isWeek) return;

  const toIso = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const parseLocalDate = (value) => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    if (!match) return null;
    const year = Number.parseInt(match[1], 10);
    const monthIndex = Number.parseInt(match[2], 10) - 1;
    const day = Number.parseInt(match[3], 10);
    const date = new Date(year, monthIndex, day);
    return Number.isNaN(date.getTime()) ? null : date;
  };

  const form = document.createElement('form');
  form.dataset.agendaDateJump = '1';
  form.method = 'get';
  form.action = window.location.pathname;
  form.setAttribute('aria-label', isMonth ? 'Ir a un mes por fecha' : 'Ir a una semana por fecha');
  form.style.display = 'flex';
  form.style.flexWrap = 'wrap';
  form.style.gap = '8px';
  form.style.alignItems = 'center';

  const input = document.createElement('input');
  input.type = 'date';
  input.className = 'form-control';
  input.required = true;
  input.style.width = 'auto';
  input.style.minWidth = '150px';

  const params = new URLSearchParams(window.location.search);
  input.value = params.get('from') || toIso(new Date());

  const button = document.createElement('button');
  button.type = 'submit';
  button.className = 'btn btn-outline-secondary btn-sm';
  button.textContent = 'Ir a fecha';

  form.append(input, button);
  quickLinks.append(form);

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const selected = parseLocalDate(input.value);
    if (!selected) return;

    const year = selected.getFullYear();
    const monthIndex = selected.getMonth();
    let from;
    let to;

    if (isMonth) {
      from = new Date(year, monthIndex, 1);
      to = new Date(year, monthIndex + 1, 0);
      params.set('view', 'month');
    } else {
      const mondayOffset = (selected.getDay() + 6) % 7;
      const weekStart = new Date(year, monthIndex, selected.getDate() - mondayOffset);
      const weekEnd = new Date(weekStart);
      weekEnd.setDate(weekStart.getDate() + 6);
      from = weekStart;
      to = weekEnd;
      params.set('view', 'calendar');
    }

    params.set('from', toIso(from));
    params.set('to', toIso(to));
    params.delete('program');
    params.delete('ticket_q');

    window.location.assign(`${window.location.pathname}?${params.toString()}`);
  });
})();