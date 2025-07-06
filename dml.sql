
INSERT INTO utente (nome, cognome, email, password, tipo)
VALUES
('Mario', 'Rossi', 'mario.rossi@mail.com', 'password123', 'gestore'),
('Luca', 'Bianchi', 'luca.bianchi@mail.com', 'password123', 'cliente'),
('Anna', 'Verdi', 'anna.verdi@mail.com', 'password123', 'cliente'),
('Laura', 'Neri', 'laura.neri@mail.com', 'password123', 'gestore');

UPDATE utente SET codice_fiscale = 'MRRSSI80A01F205X' WHERE id = 1;
UPDATE utente SET codice_fiscale = 'LCBNCH85B01F205X' WHERE id = 2;
UPDATE utente SET codice_fiscale = 'ANVRDI90C01F205X' WHERE id = 3;
UPDATE utente SET codice_fiscale = 'LRNRI95D01F205X' WHERE id = 4;

INSERT INTO utente (nome, cognome, email, password, tipo, codice_fiscale) VALUES
('Andrea', 'Gallo', 'andrea.gallo@mail.com', 'password123', 'gestore', 'NDGLL90C01F205X'),
('Sara', 'Ferri', 'sara.ferri@mail.com', 'password123', 'gestore', 'SRFRR95D01F205X'),
('Paolo', 'Conti', 'paolo.conti@mail.com', 'password123', 'cliente', 'PLCNTI91E01F205X'),
('Elisa', 'Moretti', 'elisa.moretti@mail.com', 'password123', 'cliente', 'LSMRTT92F01F205X'),
('Fabio', 'Ricci', 'fabio.ricci@mail.com', 'password123', 'cliente', 'FBRCCI93G01F205X'),
('Giulia', 'Fontana', 'giulia.fontana@mail.com', 'password123', 'cliente', 'GLFNTN94H01F205X'),
('Stefano', 'Testa', 'stefano.testa@mail.com', 'password123', 'cliente', 'SFTSTT95I01F205X'),
('Marta', 'Vitali', 'marta.vitali@mail.com', 'password123', 'cliente', 'MRTVTL96L01F205X'),
('Giorgio', 'Russo', 'giorgio.russo@mail.com', 'password123', 'cliente', 'GRGRSS97M01F205X'),
('Alessia', 'Marino', 'alessia.marino@mail.com', 'password123', 'cliente', 'LSMRNN98N01F205X'),
('Davide', 'Greco', 'davide.greco@mail.com', 'password123', 'cliente', 'DVDGRC99P01F205X');

INSERT INTO negozio (indirizzo, orario_apertura, orario_chiusura, responsabile)
VALUES
('Corso Buenos Aires 10, Milano', '09:00', '20:00', 1),
('Via Garibaldi 5, Torino', '10:00', '21:00', 4),
('Viale Liberta, Monza', '12:00', '22:00', 1);

INSERT INTO negozio (indirizzo, orario_apertura, orario_chiusura, responsabile) VALUES
('Via Vittorio Emanuele 12, Milano', '09:00', '20:00', 1),
('Corso Italia 45, Torino', '10:00', '21:00', 5),
('Piazza Duomo 8, Firenze', '11:00', '22:00', 6),
('Via Roma 33, Napoli', '09:30', '20:30', 5),
('Via Libertà 55, Palermo', '10:00', '21:00', 6);

update negozio set eliminato = true where id = 12;

INSERT INTO fornitore (partita_iva, indirizzo)
VALUES
('IT12345678901', 'Via Milano 1, Milano'),
('IT98765432109', 'Via Roma 22, Torino'),
('IT22233344455', 'Via Manzoni 10, Milano'),
('IT55566677788', 'Corso Francia 18, Torino'),
('IT88899900011', 'Piazza Repubblica 25, Firenze'),
('IT11122233344', 'Via Toledo 5, Napoli'),
('IT44455566677', 'Viale Regione Siciliana 120, Palermo');

INSERT INTO prodotto (nome, descrizione)
VALUES
('Pizza Margherita', 'Pizza con pomodoro e mozzarella'),
('Birra', 'Birra artigianale 33cl'),
('Acqua', 'Bottiglia di acqua naturale 50cl');
('Pizza Margherita', 'Pizza con pomodoro, mozzarella e basilico'),
('Pizza Diavola', 'Pizza con salame piccante e mozzarella'),
('Birra Artigianale', 'Birra rossa artigianale 33cl'),
('Acqua Naturale', 'Bottiglia di acqua naturale 50cl'),
('Acqua Frizzante', 'Bottiglia di acqua frizzante 50cl'),
('Pane Integrale', 'Pane artigianale integrale da 500g'),
('Focaccia', 'Focaccia ligure con olio extravergine'),
('Caffè Espresso', 'Miscela arabica torrefatta'),
('Cornetto Vuoto', 'Cornetto sfogliato'),
('Cornetto alla Crema', 'Cornetto ripieno di crema pasticcera'),
('Succhi di Frutta', 'Succo alla pesca 200ml'),
('Mela', 'Mela rossa del Trentino'),
('Banana', 'Banana equador biologica'),
('Mozzarella di Bufala', 'Mozzarella DOP campana 250g'),
('Prosciutto Crudo', 'Prosciutto stagionato 18 mesi'),
('Insalata Mista', NULL),
('Yogurt Bianco', 'Yogurt naturale intero 125g'),
('Tè Freddo Limone', 'Bottiglia di tè freddo al limone 500ml'),
('Cioccolato Fondente', NULL),
('Patatine Chips', 'Confezione patatine croccanti 50g');


INSERT INTO tessera (saldo_punti, data_rilascio, negozio, utente)
VALUES
(0, '2025-06-01', 4, 2),
(0, '2025-06-10', 5, 3);


INSERT INTO prodotto_negozio (negozio, prodotto, prezzo_vendita) VALUES
(4, 1, 6.50), (5, 1, 6.20), (7, 1, 6.80), (11, 1, 6.40), (13, 1, 6.55),
(4, 3, 2.10), (5, 3, 2.00), (7, 3, 2.25), (11, 3, 2.05), (13, 3, 2.15),
(4, 5, 0.60), (5, 5, 0.58), (7, 5, 0.62), (11, 5, 0.59), (13, 5, 0.61),
(4,10, 1.00), (5,10, 1.05), (7,10, 0.98), (11,10, 1.10), (13,10, 1.00),
(14, 1, 6.45), (12, 1, 6.35), (10, 1, 6.50),
(14, 3, 2.18), (12, 3, 2.12), (10, 3, 2.22),
(14, 5, 0.63), (12, 5, 0.60), (10, 5, 0.65),
(14,10, 1.02), (12,10, 1.00), (10,10, 1.05);


INSERT INTO prodotto_negozio (negozio, prodotto, prezzo_vendita)
SELECT 
    4 AS negozio,  -- ad esempio assegna tutto a negozio 4
    prodotto,
    ROUND((random() * 5 + 1)::NUMERIC, 2) AS prezzo_vendita  -- prezzo casuale tra 1 e 6
FROM (
    SELECT DISTINCT fp.prodotto
    FROM fornitore_prodotto fp
    LEFT JOIN prodotto_negozio pn ON fp.prodotto = pn.prodotto
    WHERE pn.prodotto IS NULL
) AS sub;

-- funzione di controllo per vedere quali negozi vendono che prodotti
SELECT
    pn.negozio,
    p.id AS id_prodotto,
    p.nome AS nome_prodotto,
    pn.prezzo_vendita
FROM prodotto_negozio pn
JOIN prodotto p ON p.id = pn.prodotto
ORDER BY pn.negozio, p.nome;