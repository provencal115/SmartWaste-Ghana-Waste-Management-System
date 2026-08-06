<script>
window.APP_URL = <?= json_encode(rtrim(appConfig()['url'], '/'), JSON_UNESCAPED_SLASHES) ?>;
window.BASE_URL = <?= json_encode(jsBaseUrl(), JSON_UNESCAPED_SLASHES) ?>;
window.ASSET_URL = <?= json_encode(rtrim(appConfig()['url'], '/') . '/assets/', JSON_UNESCAPED_SLASHES) ?>;
</script>
