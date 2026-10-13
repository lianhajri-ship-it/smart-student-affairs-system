<?php
$uniName = getSetting('university_name', 'جامعة بيشة');
?>
<!-- Footer -->
</div><!-- /.page-content -->
<footer class="main-footer">
    <div class="container-fluid px-4">
        <div class="row align-items-start py-3 g-2">
            <div class="col-md-6 text-center text-md-start">
                <span class="text-muted small">
                    &copy; <?= date('Y') ?> — جميع الحقوق محفوظة
                    لـ <?= htmlspecialchars($uniName) ?>
                </span>
                <div class="text-muted mt-1" style="font-size:.72rem;line-height:1.6;">
                    تم تطوير هذا النظام بواسطة مشروع التخرج للطالبات في
                    كلية الحاسبات وتقنية المعلومات — تخصص الذكاء الاصطناعي
                </div>
            </div>
            <div class="col-md-6 text-center text-md-end" style="font-size:.7rem;color:#adb5bd;line-height:1.8;">
                لينا مشرع الهاجري &nbsp;·&nbsp; نوال فلاح القحطاني &nbsp;·&nbsp; لارا وهيطان &nbsp;·&nbsp; ياسمين حمد &nbsp;·&nbsp; نوت عبدالله<br>
                ريما سعد القرني &nbsp;·&nbsp; جود عبدالله القرني &nbsp;·&nbsp; أفنان محمد القرني &nbsp;·&nbsp; رباب ظفير الشمراني &nbsp;·&nbsp; منار سعد الشهراني
            </div>
        </div>
    </div>
</footer>
</div><!-- /.page-body -->
</div><!-- /.layout-wrapper -->

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/STD/assets/js/main.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/STD/assets/js/main.js') ?>"></script>

<!-- Sidebar toggle script -->
<script>
function toggleSidebar() {
    var s = document.getElementById('stdSidebar');
    var o = document.getElementById('sidebarOverlay');
    if (!s) return;
    s.classList.toggle('open');
    if (o) o.classList.toggle('show');
}
function closeSidebar() {
    var s = document.getElementById('stdSidebar');
    var o = document.getElementById('sidebarOverlay');
    if (s) s.classList.remove('open');
    if (o) o.classList.remove('show');
}
</script>

<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
