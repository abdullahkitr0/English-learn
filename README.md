# İngilizce Kelime ve Test Uygulaması

Bu proje, kullanıcıların İngilizce kelime öğrenebileceği, test çözebileceği, kelime kartlarıyla çalışabileceği ve başarılar kazanabileceği bir web uygulamasıdır. Kullanıcılar, kelime kartları ve testler ile öğrenme sürecini eğlenceli ve verimli hale getirebilir.


- **Demo:** [Demo][https://english.abdullahki.com/]
-  Yönetici : admin@admin.com  : admin123

## İçindekiler
- [Proje Hakkında](#proje-hakkında)
- [Not](#not)
- [Özellikler](#özellikler)
- [Teknolojiler](#teknolojiler)
- [Kurulum](#kurulum)
- [Veritabanı](#veritabanı)
- [Kullanım](#kullanım)
- [Kullanıcı Tarafı Özellikleri](#kullanıcı-tarafı-özellikleri)
- [Yönetici Paneli](#yönetici-paneli)
- [Katkıda Bulunma](#katkıda-bulunma)
- [İletişim Bilgileri](#iletişim-bilgileri)
- [Lisans](#lisans)

## Proje Hakkında

İngilizce Kelime ve Test Uygulaması, kullanıcıların kelime kartlarıyla çalışabileceği, test çözebileceği, başarılar kazanabileceği ve ilerlemesini takip edebileceği bir platformdur. Yönetici paneli ile içerik ve kullanıcı yönetimi yapılabilir.

## Not

Bu proje, hobi amaçlı geliştirilmiştir ve sürekli güncellenmektedir. Eksiklikler ve geliştirmeler için katkıya açıktır. Katkıda bulunmak isterseniz, [Katkıda Bulunma](#katkıda-bulunma) bölümüne göz atabilirsiniz.

## Özellikler
- Kullanıcı kaydı ve girişi
- Kelime kartları ile öğrenme
- Farklı test türleri (günlük, tekrar, kategori bazlı)
- Kategori ve kelime yönetimi (admin paneli)
- Başarı sistemi ve istatistikler
- API desteği (JSON)
- Responsive ve modern arayüz
- Oturum ve admin güvenliği

## Teknolojiler
- **Frontend:** HTML, CSS, JavaScript, Tabler.io, Bootstrap
- **Backend:** PHP
- **Veritabanı:** MySQL/MariaDB
- **Kütüphaneler:** jQuery, PDO

## Kurulum

### Gereksinimler
- PHP 8.x veya üzeri
- MySQL/MariaDB
- Apache/Nginx web sunucusu
- Composer (isteğe bağlı)

### Adım Adım Kurulum
1. **Depoyu Klonlayın:**
   ```bash
   git clone https://github.com/abdullahkitr0/English-learn.git
   cd English-learn
   ```
2. **Veritabanı Ayarları:**
   - `nowdatabase.sql` dosyasını phpMyAdmin veya terminal ile içe aktarın. (Yaklaşık 1800 Kelime Vardır , Kelime İstemiyorsanız `databese.sql` dosyasını import edebilirsiniz.)
   - `config/config.php` dosyasındaki veritabanı bilgilerinizi güncelleyin.
3. **Yazma İzinleri:**
   - `uploads/words/`, `uploads/audio/`, `uploads/images/` klasörlerinin yazılabilir olduğundan emin olun.
   ```bash
   chmod -R 755 uploads
   ```
4. **Sunucuyu Başlatın:**
   - Apache/Nginx ile projeyi çalıştırın.

## Veritabanı

Veritabanı dosyasını indirmek için [buraya](https://github.com/abdullahkitr0/English-learn/blob/main/nowdatabase.sql) tıklayın.

## Kullanım
- Uygulamayı başlatın ve tarayıcınızda `http://localhost/English-learn` adresine gidin.
- Kullanıcı kaydı oluşturun veya giriş yapın.
- Kelime kartlarıyla çalışın, test çözün, başarılar kazanın.

## Kullanıcı Tarafı Özellikleri

1. **Ana Sayfa:**
   - Günün kelimeleri ve istatistikler görüntülenir.
2. **Kelime Kartları:**
   - Kartlarla kelime öğrenme, telaffuz dinleme, kategoriye göre filtreleme.
3. **Testler:**
   - Günlük, tekrar ve kategori bazlı testler çözme.
4. **Profil:**
   - Kullanıcı bilgileri, başarılar ve istatistikler.
5. **Kendi Kelime ve Test Listem:**
   - Öğrenilen kelimeler ve yapılan testler listelenir.
6. **İletişim:**
   - İletişim formu ile mesaj gönderme.

## Yönetici Paneli

1. **Giriş Yapma:**
   - Admin hesabı ile giriş yapılır.
2. **Kullanıcı Yönetimi:**
   - Kullanıcı ekleme, silme, düzenleme, aktif/pasif yapma.
3. **Kelime Yönetimi:**
   - Kelime ekleme, silme, onaylama, düzenleme.
4. **Kategori Yönetimi:**
   - Kategori ekleme, silme, düzenleme.
5. **Test Yönetimi:**
   - Test ekleme, silme, düzenleme.
6. **Raporlama:**
   - Genel istatistikler ve son aktiviteler.

## Katkıda Bulunma

Katkıda bulunmak isterseniz, lütfen aşağıdaki adımları izleyin:

1. Depoyu fork edin.
2. Yeni bir dal oluşturun (`git checkout -b feature/Özellik`).
3. Değişikliklerinizi yapın ve commit edin (`git commit -m 'Yeni özellik ekledim'`).
4. Dalınızı gönderin (`git push origin feature/Özellik`).
5. Bir pull request oluşturun.

## İletişim Bilgileri

Eğer benimle iletişime geçmek isterseniz, aşağıdaki bağlantılardan ulaşabilirsiniz:

- **Web Sitem:** [www.abdullahki.com](https://abdullahki.com)
- **Instagram:** [@abdullah.kvrak](https://www.instagram.com/abdullah.kvrak)
- **GitHub:** [abdullahkitr0](https://github.com/abdullahkitr0)
- **Linkedin:** [abdullahki](https://www.linkedin.com/in/abdullahki)

## Lisans

Bu proje MIT Lisansı altında lisanslanmıştır. Daha fazla bilgi için `LICENSE` dosyasını inceleyin.
