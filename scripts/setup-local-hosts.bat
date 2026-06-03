@echo off
:: Run as Administrator to add local domains to hosts file
echo Adding jikra.localhost and natually.localhost to hosts file...

echo. >> C:\Windows\System32\drivers\etc\hosts
echo # Jikra Multi-Tenant Local >> C:\Windows\System32\drivers\etc\hosts
echo 127.0.0.1 jikra.localhost >> C:\Windows\System32\drivers\etc\hosts
echo 127.0.0.1 natually.localhost >> C:\Windows\System32\drivers\etc\hosts

echo Done! You can now access:
echo   http://jikra.localhost   - Jikra store
echo   http://natually.localhost - Natually store
pause
