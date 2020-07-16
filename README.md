# Test

- api.html - файл спецификации
- localhost:1088 - api
- localhost:18025 - почта

### 1. Создание .env

```
cp .env.example .env
```

### 2.1 Docker Linux/Win

```
make up
```

### 2.2 Docker Mac

Необоходимо поставить http://docker-sync.io/
```
make up-mac
```

### 3. Инициализация проекта

```
make install
```
