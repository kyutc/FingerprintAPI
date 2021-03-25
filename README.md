# Fingerprint API

## Basic Usage Examples

### Python
```python
import urllib3
import json
import codecs

api_key = "CHANGEME"
reader = codecs.getreader('utf-8')
http = urllib3.PoolManager(cert_reqs='CERT_REQUIRED',
    ca_certs='CHANGEME.crt')

response = http.request("POST",
    "https://CHANGEME/v1/get_user_templates",
    fields={'api_key': api_key, 'username': 'somebody'},
    preload_content=False)

print(json.load(reader(response)))
response.release_conn()
```

### curl via shell

```shell
curl --cacert CHANGEME.crt -d "api_key=CHANGEME" -X POST "https://CHANGEME/v1/get_user_templates" -v
```

## Configuration

### Database
Using sqlite3 via shell:
```shell
sqlite3 some.sqlite3 < database.sql
```

### Self-Signed Certificate Generation and Verification
Via shell:
```shell
# Generate certificate and key using SAN (because CN is deprecated soon)
openssl req -new -newkey ec -pkeyopt ec_paramgen_curve:prime256v1 -sha256 -days 3650 -nodes -x509 \
    -keyout CHANGEME.key -out CHANGEME.crt -config <(cat <<-EOF
    [ req ]
    distinguished_name = dn
    x509_extensions = san
    prompt = no
    
    [ dn ]
    CN = CHANGEME
    
    [ san ]
    subjectAltName = @sans
    
    [ sans ]
    DNS.1 = CHANGEME
EOF
)
        
# Get the fingerprint of the certificate
openssl x509 -noout -fingerprint -sha256 < CHANGEME.crt
# Alternatively, use the certificate itself
cat CHANGEME.crt
```

### NGINX

This is a configuration to consider:
```C++
server {
	listen 443 ssl http2;
	listen [::]:443 ssl http2;

	ssl_certificate /etc/nginx/ssl/CHANGEME.crt;
	ssl_certificate_key /etc/nginx/ssl/CHANGEME.key;
	ssl_protocols TLSv1.3;

	root /var/www/CHANGEME/public;

	index index.txt;

	server_name CHANGEME;

	error_page 404 /404.txt;
	location / {
		try_files $uri $uri/ =404;
	}

	location ~ ^/v1/(.*)/?$ {
		try_files $uri $uri/ /api.php?api=$1&$args;
	}

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php.sock;
	}

}
```