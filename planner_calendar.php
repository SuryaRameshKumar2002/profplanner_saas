<?php
require 'config.php';
require_login();

$user = current_user();
$isSuper = is_super_admin();
$canManageJobs = $isSuper || is_werkgever();
$werkgeverId = current_werkgever_id();

$workers = [];
$buses = [];
if ($canManageJobs) {
  if ($isSuper) {
    $workers = $db->query("
      SELECT u.id, u.naam, wg.naam AS werkgever_naam
      FROM users u
      JOIN rollen r ON r.id = u.rol_id
      LEFT JOIN users wg ON wg.id = u.werkgever_id
      WHERE r.naam = 'werknemer' AND u.actief = 1
      ORDER BY COALESCE(wg.naam, ''), u.naam
    ")->fetchAll(PDO::FETCH_ASSOC);

    $buses = $db->query("
      SELECT b.id, b.naam, b.werkgever_id, wg.naam AS werkgever_naam
      FROM buses b
      LEFT JOIN users wg ON wg.id = b.werkgever_id
      WHERE b.actief = 1
      ORDER BY COALESCE(wg.naam, ''), b.naam
    ")->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $stmt = $db->prepare("
      SELECT u.id, u.naam
      FROM users u
      JOIN rollen r ON r.id = u.rol_id
      WHERE r.naam = 'werknemer' AND u.werkgever_id = ? AND u.actief = 1
      ORDER BY u.naam
    ");
    $stmt->execute([(int)$werkgeverId]);
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bStmt = $db->prepare("
      SELECT id, naam, werkgever_id
      FROM buses
      WHERE werkgever_id = ? AND actief = 1
      ORDER BY naam
    ");
    $bStmt->execute([(int)$werkgeverId]);
    $buses = $bStmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

include 'templates/header.php';
?>

<div class="card">
  <div class="calendar-toolbar">
    <div>
      <h2>Calendar Planner</h2>
      <p class="muted">Monthly grid with live collaboration, drag/drop, and reassignment.</p>
    </div>
    <div class="calendar-toolbar-actions">
      <span class="badge blue" id="liveState">Live: connecting</span>
      <button class="btn ghost" type="button" id="prevMonth">Prev</button>
      <button class="btn ghost" type="button" id="todayMonth">Today</button>
      <button class="btn ghost" type="button" id="nextMonth">Next</button>
    </div>
  </div>
  <h3 id="calendarTitle" style="margin-top:12px;"></h3>
</div>

<div class="card">
  <div class="pp-cal-weekdays">
    <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
  </div>
  <div id="ppCalendarGrid" class="pp-cal-grid"></div>
</div>

<div id="eventEditorBackdrop" class="editor-backdrop" hidden></div>
<div id="eventEditor" class="editor-panel" hidden>
  <h3>Edit Schedule</h3>
  <form id="eventEditForm">
    <input type="hidden" id="editEventId">
    <label>Date</label>
    <input type="date" id="editDate">

    <label>Start time</label>
    <input type="time" id="editStart">

    <label>End time</label>
    <input type="time" id="editEnd">

    <label>Status</label>
    <select id="editStatus">
      <option value="">Keep current</option>
      <option value="gepland">Gepland</option>
      <option value="afgerond">Afgerond</option>
      <option value="afgebroken">Afgebroken</option>
      <option value="verzet">Verzet</option>
      <option value="gepland_sales">Sales: gepland</option>
      <option value="afgerond_sales">Sales: afgerond</option>
    </select>

    <div id="jobOnlyFields" <?= $canManageJobs ? '' : 'style="display:none;"' ?>>
      <label>Reassign werknemer</label>
      <select id="editWerknemerId">
        <option value="">No change</option>
        <?php foreach ($workers as $w): ?>
          <option value="<?= (int)$w['id'] ?>">
            <?= h($w['naam']) ?><?= isset($w['werkgever_naam']) && $w['werkgever_naam'] !== null ? ' (' . h($w['werkgever_naam']) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Assign bus/team</label>
      <select id="editBusId">
        <option value="">No change</option>
        <option value="0">Geen bus</option>
        <?php foreach ($buses as $b): ?>
          <option value="<?= (int)$b['id'] ?>">
            <?= h($b['naam']) ?><?= isset($b['werkgever_naam']) && $b['werkgever_naam'] !== null ? ' (' . h($b['werkgever_naam']) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="editor-actions">
      <button class="btn" type="submit">Save</button>
      <button class="btn ghost" type="button" id="closeEditor">Cancel</button>
    </div>
  </form>
</div>

<style>
.calendar-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.calendar-toolbar-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pp-cal-weekdays,.pp-cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px}
.pp-cal-weekdays{margin-bottom:8px}
.pp-cal-weekdays div{font-size:12px;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px}
.pp-cal-grid{min-height:620px}
.pp-cal-day{border:1px solid #dbe2ea;border-radius:10px;background:#fff;padding:8px;min-height:128px;display:flex;flex-direction:column;gap:6px}
.pp-cal-day.muted{background:#f8fafc}
.pp-cal-day.today{border-color:#22c55e;box-shadow:inset 0 0 0 1px #86efac}
.pp-day-title{font-size:13px;font-weight:700;color:#334155}
.pp-day-list{display:grid;gap:5px}
.pp-event{display:block;text-decoration:none;font-size:11px;line-height:1.3;padding:5px 7px;background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #16a34a;border-radius:6px;color:#0f172a;cursor:pointer}
.pp-event.dragging{opacity:.45}
.pp-cal-day.drop-target{outline:2px dashed #16a34a;outline-offset:-4px}
.editor-backdrop{position:fixed;inset:0;background:rgba(2,6,23,.42);z-index:190}
.editor-panel{position:fixed;right:16px;top:84px;width:min(92vw,360px);max-height:calc(100dvh - 100px);overflow:auto;background:#fff;border:1px solid #dbe2ea;border-radius:12px;padding:14px;z-index:191;box-shadow:0 20px 40px rgba(2,6,23,.18)}
.editor-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
@media (max-width: 768px){
  .pp-cal-weekdays,.pp-cal-grid{gap:6px}
  .pp-cal-day{min-height:110px;padding:6px}
  .editor-panel{left:8px;right:8px;top:70px;width:auto;max-height:calc(100dvh - 80px)}
}
</style>

<script>
(() => {
  const role = <?= json_encode($user['rol'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  const canManageJobs = <?= $canManageJobs ? 'true' : 'false' ?>;
  const gridEl = document.getElementById('ppCalendarGrid');
  const titleEl = document.getElementById('calendarTitle');
  const liveStateEl = document.getElementById('liveState');
  const prevBtn = document.getElementById('prevMonth');
  const nextBtn = document.getElementById('nextMonth');
  const todayBtn = document.getElementById('todayMonth');

  const editor = document.getElementById('eventEditor');
  const editorBackdrop = document.getElementById('eventEditorBackdrop');
  const closeEditorBtn = document.getElementById('closeEditor');
  const form = document.getElementById('eventEditForm');
  const editEventId = document.getElementById('editEventId');
  const editDate = document.getElementById('editDate');
  const editStart = document.getElementById('editStart');
  const editEnd = document.getElementById('editEnd');
  const editStatus = document.getElementById('editStatus');
  const editWerknemerId = document.getElementById('editWerknemerId');
  const editBusId = document.getElementById('editBusId');
  const jobOnlyFields = document.getElementById('jobOnlyFields');

  let viewDate = new Date();
  let pollTimer = null;
  let lastUpdated = '';
  let currentEventsMap = {};
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  const toYmd = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
  const getMonthRange = (base) => {
    const monthStart = new Date(base.getFullYear(), base.getMonth(), 1);
    const monthEnd = new Date(base.getFullYear(), base.getMonth() + 1, 0);
    const start = new Date(monthStart);
    const startIsoDay = start.getDay() || 7;
    start.setDate(start.getDate() - (startIsoDay - 1));
    const end = new Date(monthEnd);
    const endIsoDay = end.getDay() || 7;
    end.setDate(end.getDate() + (7 - endIsoDay) + 1);
    return { monthStart, monthEnd, start, end };
  };

  const loadEvents = async (silent=false) => {
    const { start, end } = getMonthRange(viewDate);
    if (!silent) {
      liveStateEl.textContent = 'Live: syncing';
      liveStateEl.className = 'badge blue';
    }
    const response = await fetch(`planner_calendar_data.php?start=${toYmd(start)}&end=${toYmd(end)}`, { credentials:'same-origin' });
    const data = await response.json();
    if (!data.ok) throw new Error(data.error || 'Load failed');

    if (lastUpdated && data.last_updated && data.last_updated !== lastUpdated) {
      liveStateEl.textContent = 'Live: updated now';
      liveStateEl.className = 'badge green';
      setTimeout(() => { liveStateEl.textContent = 'Live: connected'; liveStateEl.className = 'badge blue'; }, 1800);
    } else {
      liveStateEl.textContent = 'Live: connected';
      liveStateEl.className = 'badge blue';
    }
    lastUpdated = data.last_updated || lastUpdated;
    render(data.events || []);
  };

  const render = (events) => {
    currentEventsMap = {};
    events.forEach((ev) => { currentEventsMap[ev.id] = ev; });

    const byDate = {};
    events.forEach((ev) => {
      byDate[ev.date] = byDate[ev.date] || [];
      byDate[ev.date].push(ev);
    });

    const { monthStart, monthEnd } = getMonthRange(viewDate);
    const start = new Date(monthStart);
    const startIsoDay = start.getDay() || 7;
    start.setDate(start.getDate() - (startIsoDay - 1));
    const end = new Date(monthEnd);
    const endIsoDay = end.getDay() || 7;
    end.setDate(end.getDate() + (7 - endIsoDay));

    titleEl.textContent = `${months[viewDate.getMonth()]} ${viewDate.getFullYear()}`;
    gridEl.innerHTML = '';
    const today = toYmd(new Date());

    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      const ymd = toYmd(d);
      const dayEvents = byDate[ymd] || [];
      const inMonth = d.getMonth() === viewDate.getMonth();

      const dayEl = document.createElement('div');
      dayEl.className = `pp-cal-day${inMonth ? '' : ' muted'}${ymd === today ? ' today' : ''}`;
      dayEl.dataset.date = ymd;

      dayEl.addEventListener('dragover', (e) => {
        e.preventDefault();
        dayEl.classList.add('drop-target');
      });
      dayEl.addEventListener('dragleave', () => dayEl.classList.remove('drop-target'));
      dayEl.addEventListener('drop', async (e) => {
        e.preventDefault();
        dayEl.classList.remove('drop-target');
        const eventId = e.dataTransfer.getData('text/plain');
        if (!eventId) return;
        const ev = currentEventsMap[eventId];
        if (!ev) return;
        if (ev.kind === 'job' && !canManageJobs) return;
        if (ev.kind === 'appointment' && !['super_admin','sales_manager','sales_agent','werkgever'].includes(role)) return;

        try {
          await saveEvent({ event_id: eventId, date: ymd });
          await loadEvents(true);
        } catch (err) {
          alert(err.message || 'Move failed');
        }
      });

      const top = document.createElement('div');
      top.className = 'pp-day-title';
      top.textContent = d.getDate();
      dayEl.appendChild(top);

      const list = document.createElement('div');
      list.className = 'pp-day-list';
      dayEvents.slice(0, 6).forEach((ev) => {
        const item = document.createElement('div');
        item.className = 'pp-event';
        item.style.borderLeftColor = ev.color || '#16a34a';
        item.draggable = true;
        item.dataset.eventId = ev.id;
        item.textContent = `${ev.start ? ev.start + ' ' : ''}${ev.title}`;
        item.title = `${ev.title}\n${ev.location || ''}\n${ev.status || ''}`;
        item.addEventListener('dragstart', (e) => {
          item.classList.add('dragging');
          e.dataTransfer.setData('text/plain', ev.id);
        });
        item.addEventListener('dragend', () => item.classList.remove('dragging'));
        item.addEventListener('dblclick', () => openEditor(ev));
        list.appendChild(item);
      });

      if (dayEvents.length > 6) {
        const more = document.createElement('div');
        more.className = 'muted';
        more.style.fontSize = '11px';
        more.textContent = `+${dayEvents.length - 6} more`;
        list.appendChild(more);
      }
      dayEl.appendChild(list);
      gridEl.appendChild(dayEl);
    }
  };

  const openEditor = (ev) => {
    editEventId.value = ev.id;
    editDate.value = ev.date || '';
    editStart.value = ev.start || '';
    editEnd.value = ev.end || '';
    editStatus.value = '';
    if (editWerknemerId) editWerknemerId.value = '';
    if (editBusId) editBusId.value = '';
    jobOnlyFields.style.display = (ev.kind === 'job' && canManageJobs) ? '' : 'none';
    editor.hidden = false;
    editorBackdrop.hidden = false;
  };

  const closeEditor = () => {
    editor.hidden = true;
    editorBackdrop.hidden = true;
  };

  const saveEvent = async (payload) => {
    const resp = await fetch('planner_calendar_update.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await resp.json();
    if (!resp.ok || !data.ok) {
      throw new Error(data.error || 'Save failed');
    }
    return data;
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const eventId = editEventId.value;
    const event = currentEventsMap[eventId];
    if (!event) return;

    let status = editStatus.value;
    if (status === 'gepland_sales') status = 'gepland';
    if (status === 'afgerond_sales') status = 'afgerond';

    const payload = {
      event_id: eventId,
      date: editDate.value || undefined,
      start: editStart.value || undefined,
      end: editEnd.value || undefined,
      status: status || undefined,
    };
    if (event.kind === 'job' && canManageJobs) {
      if (editWerknemerId && editWerknemerId.value !== '') payload.werknemer_id = parseInt(editWerknemerId.value, 10);
      if (editBusId && editBusId.value !== '') payload.bus_id = parseInt(editBusId.value, 10);
    }

    try {
      await saveEvent(payload);
      closeEditor();
      await loadEvents(true);
    } catch (err) {
      alert(err.message || 'Save failed');
    }
  });

  closeEditorBtn.addEventListener('click', closeEditor);
  editorBackdrop.addEventListener('click', closeEditor);

  prevBtn.addEventListener('click', async () => {
    viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
    await loadEvents();
  });
  nextBtn.addEventListener('click', async () => {
    viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
    await loadEvents();
  });
  todayBtn.addEventListener('click', async () => {
    viewDate = new Date();
    await loadEvents();
  });

  const startPolling = () => {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
      loadEvents(true).catch(() => {
        liveStateEl.textContent = 'Live: reconnecting';
        liveStateEl.className = 'badge yellow';
      });
    }, 10000);
  };

  loadEvents().catch((err) => {
    liveStateEl.textContent = 'Live: failed';
    liveStateEl.className = 'badge red';
    console.error(err);
  });
  startPolling();
})();
</script>

<?php include 'templates/footer.php'; ?>
