#BASE_URL=http://lims.sky.nankai.edu.cn/index.php
BASE_URL=http://jia.huang.lims2.test.genee/index.php/~genee
defaults write com.geneegroup.LIMSLogon KeepAliveURL "$BASE_URL/\!equipments/computer/keepalive"
defaults write com.geneegroup.LIMSLogon LoginURL "$BASE_URL/\!equipments/computer/login"
defaults write com.geneegroup.LIMSLogon LogoutURL "$BASE_URL/\!equipments/computer/logout"
defaults write com.geneegroup.LIMSLogon ProjectsURL "$BASE_URL/\!labs/projects"
defaults write com.geneegroup.LIMSLogon PublicRSA "-----BEGIN PUBLIC KEY-----\n\
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0VBFR4S5QjRc/1/K3YSC\n\
gl5lO92aqsxS3/R2GTS9c1ZWQwmFkJtMV3WlU52F105HL6oDh8EsdA/OBiAaS+yH\n\
dipoUaFvoEdcWLQ7j/Ew2Jy70X+EanTOwFjtAveo0/tw5mDyImBRTA414DUAoHUB\n\
P+q9gAq/Uj1u3nZLzFFu7yvK9AE1J5SZwdV6wHQObxAJraLofhSNSvP2r9EZMDWF\n\
3S2zyjHRLA6WEnLgp5UuUsch/IUI8BXy+pExi2Nti/xsxB+SgsGn4KUjoo1UG12g\n\
NyqO9R4p4wZO6BDuG9kE2R6jcA1pHW9jZkp5s06Karo/aKm2gpZxeBDqG/xR+rIL\n\
/wIDAQAB\n\
-----END PUBLIC KEY-----\n\
"
defaults write com.geneegroup.LIMSLogon PasswordURL "$BASE_URL/\!equipments/computer/offline_password"
