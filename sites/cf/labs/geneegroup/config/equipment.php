<?php
$config['private_key'] = "
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAoTrMda/MkkzgRurRDXTOwbTZypi8zjzvdEr/G6PWIlMnNNcW
326H9Gt8AxtYLJEiLl8im/4O59wCYOMGRJPBgM2lZcqolV3JgyJs91kL7rSvhMzn
T9PDcSlQFKxQ15UpGHNvF5SxOsV29DiZGGyLplBG3c2tP+cjj3lxeKpvxmNJpJ0i
9inZauQOkxx8TeLSTnkQL6BmywvG+H3S5Wk2WobARBbyRMiHLbLDWAn1ia/WcHjd
iECSsGVYuzIy2N2Ljx+bJ7OpsHfKgtYls75Ak0ACouq1lZbM1Tg9oQdMrfBtQ3Gf
z5q5HGKPiBik1khxg2yRHpuVgepCiZ85nqzBzQIDAQABAoIBAB3vVNbk9Q6Ux29j
Wb0t2tWMRoOfKC8CkVL+Oa9gE/n7hmQBK3YAh62R50yMXyqnJ7mEYGCmIw5ZgveK
Lze8V0ka5YKoxfQ1nZyDX8fs+JIxagU2JhxkLP1ttjigZcIhJ6gqho3SRbWrxBJE
D+jA/oy6iu3Tqj8bIzcYTUEiI+6pZRvBafTNLVkaQPOgRALnaqRj9AjCv/i1NhlB
H8PwMEoO7TzCVtcK8SSI1QDiO+g/8r9tLOPSKielpAE9k4Sq3w8PWfcHZq33hP+o
Y5O8XaVmz8w6Qwoh8+Ifr19EJu1eFoCXbhGkmGUCkYfijUdpIdzWF1Idrb0WgyeF
jBG6YuECgYEA0xOtxAQBEQvHgFvFlMudyTxfG8EkpjRzdMxaaTtc4Z2UHZ+EYcHG
87urB6EAC8fTvHFe1WpjQFDNPvSeoeDOViqBrw8L1V96HsQtYWF6L6lHczKoW4Ic
NrXRs+TQUAOcYr4S4d/Jt7nNYjttM1FhF5BsqxctHl5AA39SxWHkrd8CgYEAw4s+
W0fY9Vi5YKYP4o+pkX2DTuIjX7yxQYq+veGXb0ipb2YjZ2o6H9PG5sQu1zDdDGBi
/NGjzV7Bw8rrUvvLzlizRq5sS7Qp38UcKOJXOXCGgTyLafkeDrckMTneU1AtP5EG
6YGveAKVYD7zXyHNZ6AGCd/xE8csWFQA+bkG7dMCgYEAzqX7f2Z0LN4daXtviud2
COhELQYA/X9ocbcH5PKrUm9V7VKY5qQyRbk8DnH/e4kdsOZFdCd+GB+DcdlH3TAc
kpt2746JhVK+WpSx4R7v4u2V+CBmV4CgYqfLMJYZo9yFJN712ZGhCXCstTl9Bbrs
lYdd/HrqP0sC3OmwfXID0n0CgYEAk1wfxdJDIcGXEcqTNf1loAqiJZQtbDxaqDXS
wG19HZP4e8bQ72ISI2IJBmbZlblxG56XekbR1jaOduo4pPS0BfC6SY2wduxykfuM
2RKZAORXuJTTyyy9BgHl+GLPtKE7OCgmuVnNzfbEcx99cDec/3aMlmx41JrIRFgp
1AcnvZkCgYBBHnGqBXwgQwaSmscGoRcNasEDmefZP4Qvr6USeVuTpE8zNZFzjI6F
hF96X5W8EqdFef6meWgsuyvcNDZTc/82SLpImRXQwcV8owmzquP2UvxBByTqdfv/
7fsr0wzRcjkNQLH5EdnmP3dpq4MWLYlC50mmi3QLVdct3/Rl7i7YpQ==
-----END RSA PRIVATE KEY-----
";

$config['yiqikong.weight'] = 1000;

$config['default_capture_upload_to'] = 'local';
$config['capture_upload_to']['local'] = [
    'address' => 'http://192.168.0.2/lims/!eq_mon/capture/upload.%id'
];
