# Scripts

Esta pasta contém scripts utilitários para o projeto.

## Git Hooks

### Pre-Commit Hook

O hook de pre-commit executa verificações de qualidade antes de permitir um commit.

**Compatível com Windows, Linux e macOS** 
- Hook executa **dentro do Docker container**
- Não precisa de PHP instalado no host
- Requer apenas Docker e Git

**Instalação:**

```bash
# Via Makefile (recomendado - funciona em Linux/Mac/Windows)
make install-hooks

# Linux/Mac
chmod +x scripts/install-hooks.sh
./scripts/install-hooks.sh

# Windows PowerShell
.\scripts\install-hooks.ps1
```

**O que o hook verifica:**

1. **PHP CS Fixer** - Formatação de código
   - Verifica se o código segue PSR-12 e regras do projeto
   - Se falhar: rode `make cs-fix` para corrigir automaticamente

2. **PHPStan (nível 9)** - Análise estática
   - Detecta erros de tipo, métodos inexistentes, etc.
   - Se falhar: corrija os erros ou ajuste `phpstan.neon.dist`

**Fluxo:**

```
git add .
  |
  v
git commit -m "mensagem"
  |
  v
[Hook executa automaticamente]
  |-- PHP CS Fixer check
  |-- PHPStan analyse
  |
  v
Se OK: Commit permitido
Se ERRO: Commit bloqueado
```

**Bypass (não recomendado):**

```bash
git commit --no-verify -m "mensagem"
```

## Arquivos

- `pre-commit` - Script do hook (copiado para `.git/hooks/`)
- `install-hooks.sh` - Instalador para Linux/Mac
- `install-hooks.ps1` - Instalador para Windows PowerShell
- `README.md` - Este arquivo

## Manutenção

Se você modificar `scripts/pre-commit`, execute novamente:

```bash
make install-hooks
```

Isso copiará a versão atualizada para `.git/hooks/`.
