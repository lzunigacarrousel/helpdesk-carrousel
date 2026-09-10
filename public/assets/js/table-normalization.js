(() => {
  const normalize = () => {
    document.querySelectorAll('.content table:not([data-table-skip])').forEach((table) => {
      const headers = [...table.querySelectorAll('thead tr:first-child th')].map((th) => th.textContent.trim());
      if (!headers.length) return;

      table.classList.add('data-table');

      const parent = table.parentElement;
      if (parent?.classList.contains('table-responsive')) parent.classList.add('data-table-wrap');
      else if (!parent?.classList.contains('data-table-wrap')) {
        const wrap = document.createElement('div');
        wrap.className = 'data-table-wrap';
        table.before(wrap);
        wrap.appendChild(table);
      }

      table.querySelectorAll('tbody tr').forEach((row) => {
        [...row.children].forEach((cell, index) => {
          if (cell.tagName !== 'TD' || cell.hasAttribute('data-label')) return;
          const label = headers[index] || '';
          cell.setAttribute('data-label', label);
        });
      });
    });
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', normalize, { once: true });
  else normalize();
})();
