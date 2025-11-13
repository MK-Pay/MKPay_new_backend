1. Configurar `MAIL_ERROR_TO` no `.env` exemplo:
```sh
MAIL_ERROR_TO=dev@example.com
```

2. Testar em desenvolvimento:
# Teste direto do email
curl http://localhost:8000/dev/test-error-email

# Dispara erro runtime
curl http://localhost:8000/dev/trigger-error

# Dispara erro de database
curl http://localhost:8000/dev/trigger-db-error

3. Em produção:
- Os emails são enviados automaticamente quando ocorre um erro crítico
- Apenas em ambientes production ou staging

### HTTP Teste direto do email
```http
GET http://localhost:8001/dev/test-error-email
```

### HTTP Dispara erro runtime
```http
GET http://localhost:8001/dev/trigger-error
```

### HTTP Dispara erro de database
```http
GET http://localhost:8001/dev/trigger-db-error
```
