async function apiJson(url, opts) {
  const res = await fetch(url, opts);
  let data = {};
  try {
    data = await res.json();
  } catch (_) {
    data = { ok: false, error: 'invalid server response' };
  }
  return { res, data };
}

function renderHouses(container, houses) {
  container.innerHTML = '';
  if (!houses || houses.length === 0) {
    container.innerHTML = '<p class="muted">No houses found.</p>';
    return;
  }

  for (const h of houses) {
    const node = document.createElement('article');
    node.className = 'house';
    node.innerHTML = `
      <strong>${h.title}</strong>
      <div>${h.city} | $${h.price}</div>
      <div>visibility=${h.visibility}</div>
      <div>${h.description}</div>
      <div><small>house_id=${h.id} owner_id=${h.owner_id}</small></div>
      <img src="${h.image_path}" alt="house image">
    `;
    container.appendChild(node);
  }
}

(function setupLogin() {
  const form = document.getElementById('login-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('login-msg');
    msg.textContent = '';

    const fd = new FormData(form);
    const { data } = await apiJson('/api/login', { method: 'POST', body: fd });

    if (!data.ok) {
      msg.textContent = data.error || 'login failed';
      return;
    }

    window.location.href = '/market';
  });
})();

(function setupRegister() {
  const form = document.getElementById('register-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('register-msg');
    msg.textContent = '';

    const fd = new FormData(form);
    const { data } = await apiJson('/api/register', { method: 'POST', body: fd });

    if (!data.ok) {
      msg.textContent = data.error || 'registration failed';
      return;
    }

    msg.style.color = '#166534';
    msg.textContent = `registered user_id=${data.id}`;
    form.reset();
  });
})();

(function setupMarket() {
  const searchForm = document.getElementById('search-form');
  if (!searchForm) return;

  const searchMsg = document.getElementById('search-msg');
  const searchResults = document.getElementById('search-results');
  const myResults = document.getElementById('my-results');
  const uploadForm = document.getElementById('upload-form');

  searchForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    searchMsg.textContent = '';

    const q = new FormData(searchForm).get('q') || '';
    const { data } = await apiJson(`/api/houses/search?q=${encodeURIComponent(q)}`);

    if (!data.ok) {
      searchMsg.textContent = data.error || 'search failed';
      return;
    }

    renderHouses(searchResults, data.houses);
  });

  uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const uploadMsg = document.getElementById('upload-msg');
    uploadMsg.textContent = '';

    const fd = new FormData(uploadForm);
    const { data } = await apiJson('/api/houses/upload', {
      method: 'POST',
      body: fd,
    });

    if (!data.ok) {
      uploadMsg.textContent = data.error || 'upload failed';
      return;
    }

    uploadMsg.style.color = '#166534';
    uploadMsg.textContent = `house uploaded id=${data.id}`;
    uploadForm.reset();
  });

  document.getElementById('refresh-my').addEventListener('click', async () => {
    const { data } = await apiJson('/api/houses/my');
    if (!data.ok) {
      myResults.innerHTML = `<p class="msg">${data.error || 'failed to load my houses'}</p>`;
      return;
    }
    renderHouses(myResults, data.houses);
  });

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await apiJson('/api/logout', { method: 'POST' });
    window.location.href = '/login';
  });

  document.getElementById('whoami-btn').addEventListener('click', async () => {
    const { data } = await apiJson('/api/me');
    if (!data.ok) {
      alert('Not logged in');
      return;
    }
    alert(JSON.stringify(data.session));
  });
})();

(function setupProfile() {
  const form = document.getElementById('profile-form');
  if (!form) return;

  const msg = document.getElementById('profile-msg');
  const idInput = document.getElementById('profile-id');
  const usernameInput = document.getElementById('profile-username');
  const notesInput = document.getElementById('profile-notes');

  async function loadProfile() {
    const { data } = await apiJson('/api/profile');
    if (!data.ok) {
      msg.textContent = data.error || 'failed to load profile';
      return;
    }
    idInput.value = data.profile.id;
    usernameInput.value = data.profile.username;
    notesInput.value = data.profile.user_notes || '';
    msg.style.color = '#166534';
    msg.textContent = 'Profile loaded';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    msg.textContent = '';

    const fd = new FormData(form);
    const { data } = await apiJson('/api/profile', {
      method: 'POST',
      body: fd,
    });

    if (!data.ok) {
      msg.textContent = data.error || 'failed to update profile';
      return;
    }

    msg.style.color = '#166534';
    msg.textContent = 'Profile updated';
  });

  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      await apiJson('/api/logout', { method: 'POST' });
      window.location.href = '/login';
    });
  }

  loadProfile();
})();
