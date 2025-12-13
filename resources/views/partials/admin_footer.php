      <footer style="margin-top:24px;color:var(--muted);font-size:0.9rem">
        <div class="d-flex justify-content-between">
          <div>© <?php echo date('Y'); ?> SmartSpending</div>
          <div>Admin Panel</div>
        </div>
      </footer>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>/shared/app.js"></script>
  <script>
    // Sidebar toggle persistence
    (function(){
      const btn = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('adminSidebar');
      if (!btn || !sidebar) return;
      const stored = localStorage.getItem('admin.sidebar.collapsed');
      if (stored === '1') sidebar.classList.add('collapsed');
      btn.addEventListener('click', function(){
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('admin.sidebar.collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
      });
    })();
  </script>
</body>
</html>
