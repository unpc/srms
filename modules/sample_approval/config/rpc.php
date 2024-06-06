<?php

$config['approval'] = [
    'url'=> 'http://172.17.42.1/approval/api', //todo 修改为正确地址
    'client_id' => 'lnxfkgoh84ik8uce7opmi0kqto10luqm',
    'client_secret' => 'r8hux90z3s0c0eq27jmp30tnkmya41m7'
];

$config['servers']['approval'] = [
    'public_key'=> '
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDRCHuaNOUSbAIRhmOzU2GNWWRK
/O3Xef0B1FT4Eb8OtIwW1GtxEzTwwAjWQQaEE8QtCDhL0uFRrnG2L+vRMk/1gmEe
9FvOFefJ/bumfFkH/Atd53ws5QnbSEQZFYNJOom0X9c2k58Sg5YwjZdXkooVde0T
6kEpMcAVBqeqywnunwIDAQAB
-----END PUBLIC KEY-----
',
    'scope'=>['people', 'lab'],
];

$config['private_key'] = "
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAOLQkQ2XAgYCIK2Q
Ouh87RIZR2gEMVZH5iVN87od1pIPp0fow+IB8Ag7cfStNjqSBEUfKbgFUrtlW+Xa
NyeVxI9bZllRu4nCLYDtltvJvxA1FUXCzucuxv4QGd2nZm1SmupPxay86rIue7eA
rfkfO0rYS9Lyx44jNrtGz64rjKhFAgMBAAECgYBS3Q3RYDT+CvLzVfkfjNxzu0aK
KtX3hqb/Q/5iJZqJnCxqKhC+ViTibQ1R7aIdPdFPi3YLY+54xiwPymxSCvZXFVmW
2fV9n/zZO6fK5SZPa2CqMMqFvupOv96cWxTOcsAuPfNBtdAyZxrkDKtkd6wTUn5o
iuw/M1ui0u3VcfA/9QJBAPZTSpmZUGfLl5dp/SXGwJTtmVtK9uLay+3f51oCLVzf
ln1C9QAgLZ0a8ojzdDSmlSsuFkGMd0vAak64yyM163MCQQDruRpg4gBTwk8Iu37y
esT8KMTHAl3VAUYCIryOyw8c021q7mlq2R7ygyHNBRASo2PSio97lI8e8LpNHcd7
SB9nAkEA2jBPHR07pqUlQv6kOIkD3ydTNxWA+NL73ln9cLILAoAeqhfcMt9N6CKN
gRe88EI6UYRCPI+ywAvRXqe7cBX71wJAYAUewahOCdB08VGu/IcWBsF0prxIDKRg
KC6OMHx2w388avqC5otbF95ivmj5ix4TY4gdunFhe3ED8rXWtFlEsQJAIL9mtwJG
9UItp3SyAE8PINUz2EZVvz0ufC0UNmmgW9EdlBpmROIu1k9ejYjVuKxsg5NpSBzA
+5k+R1Qm2hUsow==
-----END PRIVATE KEY-----
";
