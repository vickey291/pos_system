            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if(menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        document.addEventListener('click', function(e) {
            if(window.innerWidth <= 768) {
                if(sidebar && menuToggle && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
    <?php 
    // Flush output buffer if needed
    if(ob_get_level()) {
        ob_end_flush();
    }
    ?>
</body>
</html>