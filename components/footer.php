<?php
/**
 * ==========================================
 * FOOTER COMPONENT — warm boutique-hotel style
 * Closes .content-body + .main-container (opened in header.php)
 * ==========================================
 */

require_once __DIR__ . '/icon.php';
?>

    </main>

    <footer class="no-print" style="background: #211C18; color: #C4B3A6; position: relative; overflow: hidden;">
        <!-- Terracotta top border -->
        <div style="height: 3px; background: var(--grad-hero, linear-gradient(90deg, #A93C16 0%, #C94F1B 50%, #FF875F 100%));"></div>

        <div style="max-width: 1280px; margin: 0 auto; padding: 2.75rem 2rem 0; position: relative; z-index: 1;">
            <div style="display: grid; grid-template-columns: 1.6fr 1fr 1fr 1.2fr; gap: 3rem; margin-bottom: 2.25rem;" class="footer-grid">

                <!-- Brand -->
                <div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.1rem;">
                        <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #A93C16 0%, #C94F1B 55%, #FF875F 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(201,79,27,0.35); color: #fff;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v22"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #FFF4EC; line-height: 1.2;">Boarding Hotel</h3>
                            <p style="font-size: 0.62rem; color: #8A786B; letter-spacing: 1.5px; text-transform: uppercase;">Management System</p>
                        </div>
                    </div>
                    <p style="color: #A89A8E; font-size: 0.86rem; line-height: 1.7; margin-bottom: 0;">
                        Complete management of student accommodation, room allocations, meals, payments, and daily hostel operations.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 style="font-weight: 800; font-size: 0.82rem; margin-bottom: 1.05rem; color: #FFF4EC; letter-spacing: 0.5px;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php
                        $BU = defined('BASE_URL') ? BASE_URL : '../';
                        $links = [
                            ['Dashboard', $BU.'index.php'],
                            ['Students', $BU.'students/list.php'],
                            ['Allocations', $BU.'allocations/list.php'],
                            ['Payments', $BU.'payments/list.php'],
                        ];
                        foreach ($links as $l) {
                            echo '<li style="margin-bottom: 0.65rem;"><a href="'.$l[1].'" style="color: #A89A8E; text-decoration: none; font-size: 0.86rem; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color=\'#FF875F\';" onmouseout="this.style.color=\'#A89A8E\';">'.$l[0].'</a></li>';
                        }
                        ?>
                    </ul>
                </div>

                <!-- Modules -->
                <div>
                    <h4 style="font-weight: 800; font-size: 0.82rem; margin-bottom: 1.05rem; color: #FFF4EC; letter-spacing: 0.5px;">Modules</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php
                        $mods = [
                            ['Meals', $BU.'meals/list.php'],
                            ['Complaints', $BU.'complaints/list.php'],
                            ['Leaves', $BU.'leaves/list.php'],
                            ['Analytics', $BU.'reports/analytics.php'],
                        ];
                        foreach ($mods as $m) {
                            echo '<li style="margin-bottom: 0.65rem;"><a href="'.$m[1].'" style="color: #A89A8E; text-decoration: none; font-size: 0.86rem; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color=\'#FF875F\';" onmouseout="this.style.color=\'#A89A8E\';">'.$m[0].'</a></li>';
                        }
                        ?>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 style="font-weight: 800; font-size: 0.82rem; margin-bottom: 1.05rem; color: #FFF4EC; letter-spacing: 0.5px;">Get in Touch</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.9rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(255,135,95,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #FF875F;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <a href="mailto:info@boardingschool.edu" style="color: #A89A8E; font-size: 0.83rem; font-weight: 500; text-decoration: none;">info@boardingschool.edu</a>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(255,135,95,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #FF875F;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                            </div>
                            <a href="tel:+8801000000000" style="color: #A89A8E; font-size: 0.83rem; font-weight: 500; text-decoration: none;">+880 1000-000000</a>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(255,135,95,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #FF875F;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <span style="color: #A89A8E; font-size: 0.83rem; font-weight: 500;">Comilla, Bangladesh</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Bar -->
            <div style="border-top: 1px solid rgba(255,244,236,0.08); padding: 1.4rem 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;" class="footer-bottom">
                <p style="color: #786B63; font-size: 0.82rem; margin: 0;">
                    &copy; <?php echo date('Y'); ?> Boarding Hotel Management System. All rights reserved.
                </p>
                <p style="color: #786B63; font-size: 0.82rem; margin: 0;">
                    Crafted with PHP &amp; care
                </p>
            </div>
        </div>
    </footer>

    <style>
        @media (max-width: 900px) {
            .footer-grid { grid-template-columns: 1fr 1fr !important; gap: 2rem !important; }
        }
        @media (max-width: 560px) {
            .footer-grid { grid-template-columns: 1fr !important; }
            .footer-bottom { flex-direction: column !important; text-align: center; }
        }
    </style>

    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('projectSidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar) sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('hidden');
        }
    </script>

</body>
</html>
