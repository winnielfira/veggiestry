<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Layanan Pelanggan</h3>
                <ul>
                    <li><a href="javascript:void(0)" onclick="return false;">Bantuan</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Cara Berbelanja</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Kebijakan Pengembalian</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Hubungi Kami</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Jelajahi</h3>
                <ul>
                    <li><a href="javascript:void(0)" onclick="return false;">Semua Produk</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Resep & Tips</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Tentang Kami</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Karir</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Pembayaran</h3>
                <ul>
                    <li><a href="javascript:void(0)" onclick="return false;">Transfer Bank</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">E-Wallet</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">Kartu Kredit</a></li>
                    <li><a href="javascript:void(0)" onclick="return false;">COD</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Ikuti Kami</h3>
                <div class="social-links">
                    <a href="javascript:void(0)" onclick="return false;"><i class="fab fa-facebook"></i></a>
                    <a href="javascript:void(0)" onclick="return false;"><i class="fab fa-instagram"></i></a>
                    <a href="javascript:void(0)" onclick="return false;"><i class="fab fa-twitter"></i></a>
                    <a href="javascript:void(0)" onclick="return false;"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Veggiestry. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
    .main-footer {
        background-color: #40513B;
        color: white;
        padding: 40px 0 20px 0;
        margin-top: 60px;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .footer-section h3 {
        color: #9DC08B;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        font-family: 'Poppins', sans-serif;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-section ul li {
        margin-bottom: 12px;
    }

    /* Normal footer link styles - looks like working links but doesn't function */
    .footer-section ul li a {
        color: #EDF1D6;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        transition: color 0.3s ease;
        cursor: pointer;
    }

    .footer-section ul li a:hover {
        color: #9DC08B;
        text-decoration: none;
    }

    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        color: #EDF1D6;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .social-links a:hover {
        background-color: #9DC08B;
        color: white;
        transform: translateY(-2px);
    }

    .social-links a i {
        font-size: 18px;
    }

    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 20px;
        text-align: center;
    }

    .footer-bottom p {
        color: #EDF1D6;
        font-size: 14px;
        margin: 0;
        font-family: 'Poppins', sans-serif;
    }

    /* Responsive Footer */
    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }

    @media (max-width: 480px) {
        .footer-content {
            grid-template-columns: 1fr;
            gap: 15px;
            text-align: center;
        }

        .social-links {
            justify-content: center;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const footerLinks = document.querySelectorAll('.main-footer a');

        footerLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Footer link clicked but disabled:', this.textContent.trim());
                return false;
            });
        });
    });
</script>