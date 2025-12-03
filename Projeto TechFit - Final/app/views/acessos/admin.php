<h1><i class="fas fa-door-open"></i> Acessos - Administração</h1>

<section class="admin-actions">
  <div class="action-buttons">
    <a href="/acessos/relatorio" class="btn-primary"><i class="fas fa-chart-line"></i> Relatório de Utilização</a>
    <a href="/acessos" class="btn-secondary"><i class="fas fa-list"></i> Ver Todos os Acessos</a>
  </div>
</section>

<section class="card" style="margin-bottom: 32px;">
  <h2><i class="fas fa-camera"></i> Leitura de QR Code</h2>
  <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 20px auto;"></div>
  <div id="qr-reader-results" style="text-align: center; margin-top: 20px;"></div>
  <div style="text-align: center; margin-top: 20px;">
    <button id="start-scanner" class="btn-primary"><i class="fas fa-play"></i> Iniciar Scanner</button>
    <button id="stop-scanner" class="btn-secondary" style="display: none;"><i class="fas fa-stop"></i> Parar Scanner</button>
  </div>
</section>

<table class="table">
  <thead>
    <tr>
      <th>Aluno</th>
      <th>Entrada</th>
      <th>Saída</th>
      <th>Tipo</th>
      <th>Código</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($acessos as $ac): ?>
    <tr>
      <td><?= htmlspecialchars($ac['nome']) ?></td>
      <td><?= date('d/m/Y H:i', strtotime($ac['data_hora_entrada'])) ?></td>
      <td><?= $ac['data_hora_saida'] ? date('d/m/Y H:i', strtotime($ac['data_hora_saida'])) : '-' ?></td>
      <td><?= $ac['tipo_identificacao'] ?></td>
      <td><?= htmlspecialchars($ac['codigo']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrcodeScanner = null;
let isScanning = false;
let ultimoQRCodeLido = null;
let ultimaLeituraTimestamp = 0;
let processando = false;
const DELAY_ENTRE_LEITURAS = 5000;

document.getElementById('start-scanner').addEventListener('click', function() {
    if (isScanning) return;
    
    const qrReader = document.getElementById('qr-reader');
    const resultsDiv = document.getElementById('qr-reader-results');
    const startBtn = document.getElementById('start-scanner');
    const stopBtn = document.getElementById('stop-scanner');
    
    html5QrcodeScanner = new Html5Qrcode("qr-reader");
    
    html5QrcodeScanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        (decodedText, decodedResult) => {
            processarQRCode(decodedText);
        },
        (errorMessage) => {}
    ).then(() => {
        isScanning = true;
        startBtn.style.display = 'none';
        stopBtn.style.display = 'inline-block';
        resultsDiv.innerHTML = '<p style="color: var(--text-secondary);">Aponte a câmera para o QR Code do aluno...</p>';
        ultimoQRCodeLido = null;
        ultimaLeituraTimestamp = 0;
    }).catch((err) => {
        console.error('Erro ao iniciar scanner:', err);
        resultsDiv.innerHTML = '<p style="color: var(--accent-color);">Erro ao acessar a câmera. Verifique as permissões.</p>';
    });
});

document.getElementById('stop-scanner').addEventListener('click', function() {
    if (html5QrcodeScanner && isScanning) {
        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            processando = false;
            ultimoQRCodeLido = null;
            ultimaLeituraTimestamp = 0;
            document.getElementById('start-scanner').style.display = 'inline-block';
            document.getElementById('stop-scanner').style.display = 'none';
            document.getElementById('qr-reader-results').innerHTML = '';
        }).catch((err) => {
            console.error('Erro ao parar scanner:', err);
        });
    }
});

function processarQRCode(qrData) {
    if (!isScanning || processando) return;
    
    const agora = Date.now();
    const tempoDesdeUltimaLeitura = agora - ultimaLeituraTimestamp;
    
    if (ultimoQRCodeLido === qrData && tempoDesdeUltimaLeitura < DELAY_ENTRE_LEITURAS) {
        const segundosRestantes = Math.ceil((DELAY_ENTRE_LEITURAS - tempoDesdeUltimaLeitura) / 1000);
        const resultsDiv = document.getElementById('qr-reader-results');
        resultsDiv.innerHTML = `
            <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); 
                        padding: 16px; border-radius: 8px; margin-top: 10px;">
                <p style="color: #ffc107; font-weight: 600; margin: 0;">
                    <i class="fas fa-clock"></i> Aguarde ${segundosRestantes} segundo(s) antes de ler novamente
                </p>
            </div>
        `;
        return;
    }
    
    try {
        const dados = JSON.parse(qrData);
        
        if (!dados.nome || !dados.email || !dados.senha) {
            throw new Error('QR Code inválido');
        }
        
        processando = true;
        ultimoQRCodeLido = qrData;
        ultimaLeituraTimestamp = agora;
        
        const resultsDiv = document.getElementById('qr-reader-results');
        resultsDiv.innerHTML = '<p style="color: var(--primary-color);"><i class="fas fa-spinner fa-spin"></i> Processando acesso...</p>';
        
        fetch('/acessos/registrar-qrcode', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dados)
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                resultsDiv.innerHTML = `
                    <div style="background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); 
                                padding: 16px; border-radius: 8px; margin-top: 10px;">
                        <p style="color: #4caf50; font-weight: 600; margin: 0;">
                            <i class="fas fa-check-circle"></i> Acesso registrado com sucesso!
                        </p>
                        <p style="margin: 8px 0 0 0; color: var(--text-secondary);">
                            Aluno: ${dados.nome}<br>
                            Email: ${dados.email}<br>
                            Horário: ${new Date().toLocaleString('pt-BR')}
                        </p>
                    </div>
                `;
                
                setTimeout(() => {
                    processando = false;
                    ultimoQRCodeLido = null;
                    ultimaLeituraTimestamp = Date.now();
                    location.reload();
                }, 2000);
            } else {
                processando = false;
                resultsDiv.innerHTML = `
                    <div style="background: rgba(244, 67, 54, 0.1); border: 1px solid rgba(244, 67, 54, 0.3); 
                                padding: 16px; border-radius: 8px; margin-top: 10px;">
                        <p style="color: #f44336; font-weight: 600; margin: 0;">
                            <i class="fas fa-exclamation-circle"></i> ${data.erro || 'Erro ao registrar acesso'}
                        </p>
                    </div>
                `;
                setTimeout(() => {
                    processando = false;
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            processando = false;
            resultsDiv.innerHTML = `
                <div style="background: rgba(244, 67, 54, 0.1); border: 1px solid rgba(244, 67, 54, 0.3); 
                            padding: 16px; border-radius: 8px; margin-top: 10px;">
                    <p style="color: #f44336; font-weight: 600; margin: 0;">
                        <i class="fas fa-exclamation-circle"></i> Erro ao processar QR Code
                    </p>
                </div>
            `;
            setTimeout(() => {
                processando = false;
            }, 2000);
        });
    } catch (error) {
        processando = false;
        const resultsDiv = document.getElementById('qr-reader-results');
        resultsDiv.innerHTML = `
            <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); 
                        padding: 16px; border-radius: 8px; margin-top: 10px;">
                <p style="color: #ffc107; font-weight: 600; margin: 0;">
                    <i class="fas fa-exclamation-triangle"></i> QR Code inválido ou formato incorreto
                </p>
            </div>
        `;
        setTimeout(() => {
            processando = false;
        }, 2000);
    }
}
</script>
