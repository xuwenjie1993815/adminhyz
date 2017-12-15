<?php
return array(


	//'配置项'=>'配置值'
    'LOAD_EXT_CONFIG' => 'db', //扩展配置文件加载
    'SUPER_ADMIN_NAME' => 'admin',
	'SUPER_ADMIN_NAME_TWO' => 'administarter',
    'IGNORES' =>[
        'Index/index'
    ],

	'URL_CASE_INSENSITIVE' =>true,
	'APP_SUB_DOMAIN_DEPLOY'   =>    false, // 开启子域名或者IP配置
    'APP_SUB_DOMAIN_RULES'    =>    array( 
        'manage' => 'Home',
		'client' => 'Client',
		// 'app' =>'Api'
    ),
	
    'OPEN_SUPER_ADMIN' => 1,
	'OPEN_SUPER_ADMIN_TWO' => 1,
    //结算扣点比例
    'RATIO'=>0,
    
    'alipay'=>array(
        'gatewayUrl'=>'https://openapi.alipay.com/gateway.do',
        'appId'=>'2017030105985869',
        'rsaPrivateKey' => 'MIIEpQIBAAKCAQEAzrOZnYGb5UN08OE+XNUhnkLbfTSJq12Zav7PFHIke15jdyvl5XWCMfsI8hAHo+j8TRe9dAeYTMj5nluHHvp+GciGJ91ziqOG9+uuKvpFJcbBPlZAOG3VVY/zOjOJqWeLgJlsk035DGeWMHIxZocWh6h9YTyGq9d/uq5r/JijFZDCS2nVsoPkqYsJOcgzN8DQEgtAPV9V/ousD8F4ZDg9yzawDcHxAV+q6MSMNeKOtduFsEvgBDD+l5ivUNJYkojlsFWPetBXaPdMbS/D8KhYV1QhBwGldhtRXqYHNR05GxhmkKr9T07t49m4rdAGrP8QRKcud7k8CN0Oz/CqeBmQJwIDAQABAoIBAQC1mJJID3tQA2C0056/XjwH14MPz913YWcM2kpCEzm77SQDqdxzKDa3BG5KhAzCU0l9zXKTgqyqIRM0rgLbE4U/z60VTvhYf+xu2W8NsQyY8LILuyj2qn/3iN1Ob8LswtyLob8C90jrPgJ0E48VHW+MxVlJ2SwTwuMjtmOG6u60XQIyI6bBgP2nuAgyLuR84e75JD0INprRtwdMBZYqGVlHLaueXH/3tbgMv3N3Z0+aqEi6MnYpzOEXww6IrlAZmvhq3PyFv7ZvXyNuHeNksIATJBWRC5FgIqRomPBoUUdAYaX+I87YE5JJXDHmmT5Asozml2pDBpN7C0tmr8Z9TLIxAoGBAOdgV5VNc+BPKP/DTkPGpaqhvIFwE6Ki0hn5cRR1P2CqTqJZdmflZW/8XEbpmcSwNfHx+60DfeRWkkz932bhuk3b00aaazXqJUw7fPb15KCw9NkL+1df+iKGkqAsII5GTZxOS8kI3yn+jarxz71+8vG7e5biFC3WCcb7qgOwZSvpAoGBAOSzA5fDJnBHBoeZ4ZQCwOE2DD8zyqT9u4N7KehSMjWeiyRLnf+HatTt9zhcMaM84fbGwFldEdRqFKaTHPLSAtTlLjIwTAkaYdGuXDHpHjHOkv8X1eWsOHhJH7NhSAqiq5yMcmF9QI7e0RnmcIyTcJegw9TTJ4uZ9fjy6yXCZSGPAoGBAIpjJz9Xif0Zm64OBJupDqFWB0dr33Kg7AU5Gpdf4T5R3qJf2+AcZmVqjU6knaH0uu8xCpTax8twtCR0m9APJr02w9EwvvsKRrCnzABx5gLulCPVdMk30IYh80T136r5BaZ9dfqR4dheNiGOa/AtI+XNewgtxF/96u8myvNV580xAoGAZcnrUnOwOw7RsC7kQM1M7a/xmXvCuNaZy/ZYe6eB422SPnBNfTrXaLgVbYdTLHVfmUdyuxN6aRFh6ZXWr5ibXAg5HGt0nCSa3wl8zVYVc1OfB1yjfhq984OQUXV97AXk3cOA8TUfM7emV6HB/D9vQH10S9hDZJX87XWpBWBVRK8CgYEAkOMtDiVQnUTH+vPpwm+PdSqqoJ8yfUo1qkC/Ung7WkLfZeWp9NGbZae/ZKDtnLZSLR2nQgDwz+AyFhiCX9hs8pBR3awQuH99iuDFjL3at4P1vtiF/EfNUh6Yz8SLU0uHltBPGjKxH9OSWxsh8YZfTUKKXuNuyVwEni9frRX3QvA=',
        'alipayrsaPublicKey'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy9+6vkChktYFJxRMN7yfhIFK2KdIfv/FodoM4vdDpPgSzW9Zwm8oARiE7nni5a9A397dJorut/1/RQduPb4zDx85YXgUF/Jvvi9yftECOy7UvPHwahsBrGcw5YtBECFm+zyMxRwIqRgDaxQAiGZ0OonYjUmdUzeM1v3BAGmfpuHsqOrj0gfQMIQOm9uWI98Al53bJZSzC1t/Odke7e/5HmIeAyRXLpWfsphEUPHIMaTUFiHLscdqDprPBFvCogojrhGhuNEKUvF4J6foE69e3uEl0BA70aBejpGyY5rA7geKPYrjstpRc2JAEvj7YEqzMxbjl8/mO3qcuEsjNeMsXQIDAQAB',       
        'apiVersion' => '1.0',
        'signType' => 'RSA2',
        'format'=>'json'
    )
);