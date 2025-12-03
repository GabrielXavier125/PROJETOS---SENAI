<h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>

<section class="admin-actions">
  <h2><i class="fas fa-bolt"></i> Ações Rápidas</h2>
  <div class="action-buttons">
    <a href="/avaliacoes/nova" class="btn-primary"><i class="fas fa-clipboard-check"></i> Nova Avaliação</a>
    <a href="/mensagens/nova" class="btn-primary"><i class="fas fa-envelope"></i> Nova Mensagem</a>
  </div>
</section>

<section>
    <h2><i class="fas fa-user-plus"></i> Novo Usuário</h2>
    <form method="POST" action="/admin/usuarios/salvar" class="form">
        <input type="hidden" name="id" id="usuario_id">
        <div class="form-group">
            <label>Nome:</label>
            <input type="text" name="nome" id="usuario_nome" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" id="usuario_email" required>
        </div>
        <div class="form-group">
            <label>Senha:</label>
            <input type="password" name="senha" id="usuario_senha" placeholder="Deixe em branco para manter a atual">
        </div>
        <div class="form-group">
            <label>Perfil:</label>
            <select name="perfil" id="usuario_perfil" required>
                <option value="aluno">Aluno</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label>Modalidade:</label>
            <input type="text" name="modalidade" id="usuario_modalidade">
        </div>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Salvar</button>
        <button type="button" class="btn-secondary" onclick="limparForm()"><i class="fas fa-times"></i> Cancelar</button>
    </form>
</section>

<section>
    <h2><i class="fas fa-list"></i> Lista de Usuários</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Perfil</th>
                <th>Modalidade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['nome']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <?php if ($u['perfil'] === 'admin'): ?>
                    <span class="badge-unread"><i class="fas fa-user-shield"></i> Admin</span>
                  <?php else: ?>
                    <span class="badge-read"><i class="fas fa-user"></i> Aluno</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['modalidade'] ?? '') ?></td>
                <td>
                    <?php if ($u['perfil'] === 'aluno'): ?>
                        <button onclick="mostrarQRCode(<?= $u['id'] ?>)" class="btn-primary"><i class="fas fa-qrcode"></i> QR Code</button>
                    <?php endif; ?>
                    <button onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)" class="btn-secondary"><i class="fas fa-edit"></i> Editar</button>
                    <form method="POST" action="/admin/usuarios/excluir" class="inline-form" onsubmit="return confirm('Tem certeza?')">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<div id="qrcodeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="fecharQRCode()">&times;</span>
        <h2><i class="fas fa-qrcode"></i> QR Code do Aluno</h2>
        <div id="qrcodeContainer" style="text-align: center; padding: 20px;"></div>
        <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
            Este QR Code contém os dados de acesso do aluno.
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function editarUsuario(usuario) {
    document.getElementById('usuario_id').value = usuario.id;
    document.getElementById('usuario_nome').value = usuario.nome;
    document.getElementById('usuario_email').value = usuario.email;
    document.getElementById('usuario_perfil').value = usuario.perfil;
    document.getElementById('usuario_modalidade').value = usuario.modalidade || '';
    document.getElementById('usuario_senha').placeholder = 'Deixe em branco para manter a atual';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limparForm() {
    document.getElementById('usuario_id').value = '';
    document.getElementById('usuario_nome').value = '';
    document.getElementById('usuario_email').value = '';
    document.getElementById('usuario_senha').value = '';
    document.getElementById('usuario_perfil').value = 'aluno';
    document.getElementById('usuario_modalidade').value = '';
    document.getElementById('usuario_senha').placeholder = '';
}

function mostrarQRCode(usuarioId) {
    fetch(`/admin/usuarios/qrcode-dados?usuario_id=${usuarioId}`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert(data.erro);
                return;
            }
            
            const modal = document.getElementById('qrcodeModal');
            const container = document.getElementById('qrcodeContainer');
            container.innerHTML = '';
            
            const qrData = JSON.stringify({
                nome: data.nome,
                email: data.email,
                senha: data.senha
            });
            
            new QRCode(container, {
                text: qrData,
                width: 300,
                height: 300,
                colorDark: "#1e293b",
                colorLight: "#ffffff"
            });
            
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao gerar QR Code');
        });
}

function fecharQRCode() {
    document.getElementById('qrcodeModal').style.display = 'none';
    document.getElementById('qrcodeContainer').innerHTML = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('qrcodeModal');
    if (event.target == modal) {
        fecharQRCode();
    }
}
</script>


