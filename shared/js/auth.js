/*
  Admin sign-in (DEMO ONLY — no real password check).
  Replace ET.auth.login with a call to the real authentication backend.
*/
(function () {
  'use strict';
  var ET = window.ET;
  var KEY = 'et_admin_session';
  var TTL = 8 * 60 * 60 * 1000;
  var memory = null; // fallback when storage is blocked

  function find(list, k, v) { return list.filter(function (x) { return x[k] === v; })[0]; }

  ET.auth = {
    user: function () {
      var s = memory;
      try { s = JSON.parse(localStorage.getItem(KEY)) || memory; } catch (e) {}
      return s && s.exp > Date.now() ? s : null;
    },
    login: function (email, password) {
      var u = find(ET.data('USERS'), 'email', String(email || '').trim().toLowerCase());
      if (!u) return { error: 'No user with that email. Try admin@example.com.' };
      if (!u.active) return { error: 'This account is disabled. Ask an administrator to enable it in Sheriff.' };
      if (!password) return { error: 'Enter your password.' };
      var s = { id: u.id, name: u.name, email: u.email, role: u.role, exp: Date.now() + TTL };
      memory = s;
      try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
      return { user: s };
    },
    logout: function () {
      memory = null;
      try { localStorage.removeItem(KEY); } catch (e) {}
    },
    perms: function () {
      var u = this.user();
      var r = u && find(ET.data('ROLES'), 'name', u.role);
      return r ? r.perms : [];
    },
    can: function (perm) { return this.perms().indexOf(perm) > -1; },
    apps: function () {
      var self = this;
      return ET.APPS.filter(function (a) { return self.can(a.key); });
    },
    // Send the visitor to sign in (or back to the launcher) if they can't be here.
    require: function (app) {
      if (!this.user()) {
        ET.go(ET.root + 'admin/login.html?next=' + encodeURIComponent(location.pathname + location.hash));
        return false;
      }
      if (app && !this.can(app)) {
        ET.go(ET.root + 'admin/?denied=' + app);
        return false;
      }
      return true;
    }
  };
})();
