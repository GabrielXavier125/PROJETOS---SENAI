<section class="carteirinha-container">
    <div class="carteirinha-card">
        <div class="carteirinha-header">
            <div class="carteirinha-logo">
                <i class="fas fa-dumbbell"></i>
                <h2>TechFit</h2>
            </div>
            <div class="carteirinha-tipo">
                <span>CARTEIRINHA DE ACESSO</span>
            </div>
        </div>
        
        <div class="carteirinha-body">
            <div class="carteirinha-info">
                <div class="carteirinha-foto">
                    <i class="fas fa-user"></i>
                </div>
                <div class="carteirinha-dados">
                    <h3><?= htmlspecialchars($usuario['nome']) ?></h3>
                    <p class="carteirinha-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($usuario['email']) ?></p>
                    <?php if (!empty($usuario['modalidade'])): ?>
                        <p class="carteirinha-modalidade"><i class="fas fa-dumbbell"></i> <?= htmlspecialchars($usuario['modalidade']) ?></p>
                    <?php endif; ?>
                    <p class="carteirinha-id"><i class="fas fa-hashtag"></i> ID: <?= $usuario['id'] ?></p>
                </div>
            </div>
            
            <div class="carteirinha-qrcode">
                <div id="qrcode-carteirinha"></div>
                <p class="carteirinha-instrucao">Apresente este QR Code na entrada da academia</p>
            </div>
        </div>
        
        <div class="carteirinha-footer">
            <p><i class="fas fa-shield-alt"></i> Válida apenas para o portador</p>
            <button onclick="window.print()" class="btn-primary"><i class="fas fa-print"></i> Imprimir Carteirinha</button>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode-carteirinha"), {
        text: <?= json_encode($qrData) ?>,
        width: 200,
        height: 200,
        colorDark: "#1e293b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

<style>
.carteirinha-container {
    max-width: 750px;
    margin: 40px auto;
    padding: 30px;
}

.carteirinha-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(77, 208, 225, 0.2);
    overflow: hidden;
    border: 2px solid var(--border-light);
    width: 100%;
}

.carteirinha-header {
    background: var(--primary-gradient);
    padding: 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.carteirinha-logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.carteirinha-logo i {
    font-size: 2rem;
}

.carteirinha-logo h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
}

.carteirinha-tipo {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    opacity: 0.9;
}

.carteirinha-body {
    padding: 40px;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 40px;
    align-items: center;
}

.carteirinha-info {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    flex: 1;
    min-width: 0;
}

.carteirinha-foto {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(77, 208, 225, 0.3);
}

.carteirinha-dados h3 {
    margin: 0 0 12px;
    font-size: 1.5rem;
    color: var(--text-primary);
    font-weight: 700;
}

.carteirinha-dados p {
    margin: 8px 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.carteirinha-dados i {
    color: var(--primary-color);
    width: 16px;
}

.carteirinha-email {
    font-weight: 500;
}

.carteirinha-modalidade {
    color: var(--primary-color);
    font-weight: 600;
}

.carteirinha-id {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.carteirinha-qrcode {
    text-align: center;
    padding: 24px;
    background: #ffffff;
    border-radius: 12px;
    border: 2px solid var(--border-light);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    min-width: 240px;
}

#qrcode-carteirinha {
    margin: 0 auto;
    padding: 16px;
    background: #fff;
    border-radius: 8px;
    display: inline-block;
}

#qrcode-carteirinha canvas,
#qrcode-carteirinha img {
    max-width: 100%;
    height: auto;
    display: block;
}

.carteirinha-instrucao {
    margin-top: 16px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.carteirinha-footer {
    background: rgba(77, 208, 225, 0.05);
    padding: 20px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--border-color);
}

.carteirinha-footer p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.carteirinha-footer i {
    color: var(--primary-color);
}

@media (max-width: 768px) {
    .carteirinha-container {
        max-width: 100%;
        padding: 20px;
    }
    
    .carteirinha-body {
        grid-template-columns: 1fr;
        gap: 32px;
        padding: 32px 24px;
    }
    
    .carteirinha-header {
        flex-direction: column;
        gap: 12px;
        text-align: center;
        padding: 20px;
    }
    
    .carteirinha-qrcode {
        min-width: auto;
        width: 100%;
    }
    
    .carteirinha-footer {
        flex-direction: column;
        gap: 16px;
        padding: 20px 24px;
    }
    
    .carteirinha-footer button {
        width: 100%;
    }
}

@media print {
    .carteirinha-footer button {
        display: none;
    }
    
    .carteirinha-container {
        margin: 0;
        padding: 0;
    }
    
    .carteirinha-card {
        box-shadow: none;
        border: 2px solid #000;
    }
}
</style>

