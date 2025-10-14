# 🔗 INSTRUÇÕES PARA INTEGRAÇÃO NO MENU ADMINISTRATIVO

Para que o **Sistema de Turmas Teóricas** apareça no menu administrativo, adicione o seguinte item ao menu principal:

## 📝 **Código para adicionar ao menu:**

```php
// No arquivo que controla o menu administrativo (geralmente admin/index.php ou similar)
// Adicionar na seção de menu de gestão acadêmica:

<li class="nav-item">
    <a class="nav-link" href="?page=turmas-teoricas">
        <i class="fas fa-chalkboard-teacher"></i>
        <span>Turmas Teóricas</span>
        <span class="badge badge-info">Novo</span>
    </a>
</li>
```

## 🎨 **Estilo sugerido (se usar sidebar com dropdown):**

```php
<li class="nav-item has-dropdown">
    <a href="#" class="nav-link has-dropdown">
        <i class="fas fa-graduation-cap"></i>
        <span>Gestão de Turmas</span>
    </a>
    <ul class="dropdown-menu">
        <li><a class="nav-link" href="?page=turmas">Turmas Individuais</a></li>
        <li><a class="nav-link" href="?page=turmas-teoricas">
            Turmas Teóricas <span class="badge badge-success">Novo</span>
        </a></li>
    </ul>
</li>
```

## 🔑 **Permissões necessárias:**

O sistema já verifica as permissões adequadas:
```php
// Já implementado no sistema:
if (!$isAdmin && !$isInstrutor) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}
```

## 📱 **Para menu mobile:**

```php
<div class="mobile-menu-item">
    <a href="?page=turmas-teoricas" class="mobile-link">
        📚 Turmas Teóricas
    </a>
</div>
```

## 🎯 **Ícones recomendados:**

- **FontAwesome:** `fas fa-chalkboard-teacher`
- **Emoji:** `📚` ou `🎓`
- **Material Icons:** `school` ou `class`

## ✅ **Verificação de integração:**

Após adicionar ao menu, teste:
1. Acesse `admin/?page=turmas-teoricas`
2. Verifique se carrega a página principal
3. Teste a criação de uma turma
4. Confirme que as permissões funcionam

---

**O sistema está 100% pronto para uso!** 🚀
