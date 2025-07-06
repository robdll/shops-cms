-- Tabella utenti
CREATE TABLE utente (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(50),
  cognome VARCHAR(50),
  email VARCHAR(100),
  password VARCHAR(100),
  tipo VARCHAR(10) CHECK (tipo IN ('gestore', 'cliente'))
);

ALTER TABLE utente
ADD COLUMN codice_fiscale VARCHAR(20);

ALTER TABLE utente
ADD CONSTRAINT unique_codice_fiscale UNIQUE (codice_fiscale);


-- Tabella fornitore
CREATE TABLE fornitore (
  partita_iva VARCHAR(20) PRIMARY KEY,
  indirizzo TEXT
);

ALTER TABLE fornitore
ALTER COLUMN indirizzo SET NOT NULL;

-- prodotto
CREATE TABLE prodotto (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(100),
  descrizione TEXT
);

ALTER TABLE prodotto
ALTER COLUMN nome SET NOT NULL;

-- negozio
CREATE TABLE negozio (
  id SERIAL PRIMARY KEY,
  indirizzo TEXT,
  orario_apertura TIME,
  orario_chiusura TIME,
  responsabile INT,
  FOREIGN KEY (responsabile) REFERENCES utente(id)
);

ALTER TABLE negozio
ADD COLUMN eliminato BOOLEAN DEFAULT FALSE;

-- tessera
CREATE TABLE tessera (
  id SERIAL PRIMARY KEY,
  saldo_punti INT,
  data_rilascio DATE,
  negozio INT,
  utente INT,
  FOREIGN KEY (negozio) REFERENCES negozio(id),
  FOREIGN KEY (utente) REFERENCES utente(id)
);

ALTER TABLE tessera
ADD CONSTRAINT unique_utente_tessera UNIQUE (utente);

CREATE TABLE ordine (
	id SERIAL PRIMARY KEY,
	negozio INT,
	fornitore VARCHAR(20),
	data_consegna DATE,
	FOREIGN KEY (negozio) REFERENCES negozio(id),
	FOREIGN KEY (fornitore) REFERENCES fornitore(partita_iva)
);

ALTER TABLE ordine RENAME TO approvvigionamento;

-- solo gli utenti di tipo gestore possono essere associati come responsabili di negozio
CREATE OR REPLACE FUNCTION check_responsabile_gestore()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM utente
        WHERE id = NEW.responsabile AND tipo = 'gestore'
    ) THEN
        RETURN NEW;
    ELSE
        RAISE EXCEPTION 'Il responsabile deve essere un utente di tipo gestore';
    END IF;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_check_responsabile
BEFORE INSERT OR UPDATE ON negozio
FOR EACH ROW
EXECUTE FUNCTION check_responsabile_gestore();

-- storico tessere. tutti i dati della tessera vengono copiati in questa tabella
-- in caso di cancellazione logica di un negozio, o di eliminazione di una tessera
CREATE TABLE storico_tessere (
  id SERIAL PRIMARY KEY,
  tessera_id INT,
  saldo_punti INT,
  data_rilascio DATE,
  negozio_id INT,
  tipo_evento VARCHAR(30) CHECK (tipo_evento IN ('chiusura_negozio', 'cancellazione_tessera')),
  data_evento TIMESTAMP DEFAULT now()
);

CREATE OR REPLACE FUNCTION copia_tessere_su_chiusura_negozio()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.eliminato = TRUE AND OLD.eliminato = FALSE THEN
        INSERT INTO storico_tessere (tessera_id, saldo_punti, data_rilascio, negozio_id, tipo_evento)
        SELECT t.id, t.saldo_punti, t.data_rilascio, t.negozio, 'chiusura_negozio'
        FROM tessera t
        WHERE t.negozio = OLD.id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_copia_tessere_su_chiusura
AFTER UPDATE ON negozio
FOR EACH ROW
EXECUTE FUNCTION copia_tessere_su_chiusura_negozio();

CREATE OR REPLACE FUNCTION copia_tessera_su_cancellazione()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO storico_tessere (tessera_id, saldo_punti, data_rilascio, negozio_id, tipo_evento)
    VALUES (OLD.id, OLD.saldo_punti, OLD.data_rilascio, OLD.negozio, 'cancellazione_tessera');
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_copia_tessera_su_delete
BEFORE DELETE ON tessera
FOR EACH ROW
EXECUTE FUNCTION copia_tessera_su_cancellazione();

-- scontrino, identifica gli acquistti B2C
CREATE TABLE scontrino (
  id SERIAL PRIMARY KEY,
  data_acquisto DATE NOT NULL,
  tessera INT NOT NULL,
  negozio INT NOT NULL,
  sconto_percentuale INT DEFAULT 0,
  totale_pagato NUMERIC(10,2) NOT NULL,
  FOREIGN KEY (tessera) REFERENCES tessera(id),
  FOREIGN KEY (negozio) REFERENCES negozio(id)
);

ALTER TABLE scontrino
ADD COLUMN utente INT;

UPDATE scontrino
SET utente = t.utente
FROM tessera t
WHERE scontrino.tessera = t.id;

select * from scontrino

ALTER TABLE scontrino
ALTER COLUMN utente SET NOT NULL;

ALTER TABLE scontrino
ADD FOREIGN KEY (utente) REFERENCES utente(id);

CREATE OR REPLACE FUNCTION check_coerenza_scontrino()
RETURNS TRIGGER AS $$
DECLARE
    proprietario INT;
BEGIN
    -- se lo scontrino non ha tessera, tutto ok
    IF NEW.tessera IS NULL THEN
        RETURN NEW;
    END IF;

    -- recupera utente proprietario della tessera
    SELECT utente INTO proprietario
    FROM tessera
    WHERE id = NEW.tessera;

    -- controlla se combacia
    IF proprietario IS NULL OR proprietario <> NEW.utente THEN
        RAISE EXCEPTION 'Utente % non è proprietario della tessera %', NEW.utente, NEW.tessera;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_check_coerenza_scontrino
BEFORE INSERT OR UPDATE ON scontrino
FOR EACH ROW
EXECUTE FUNCTION check_coerenza_scontrino();

-- scontrino_prodotto contiene i record che relaziona uno scontrino ai prodotti acquistati
CREATE TABLE scontrino_prodotto (
    scontrino INT NOT NULL,
    prodotto INT NOT NULL,
    prezzo_unitario NUMERIC(10,2) NOT NULL,
    quantita INT NOT NULL,
    PRIMARY KEY (scontrino, prodotto),
    FOREIGN KEY (scontrino) REFERENCES scontrino(id),
    FOREIGN KEY (prodotto) REFERENCES prodotto(id)
);

-- la quantità deve essere positiva
ALTER TABLE scontrino_prodotto
ADD CONSTRAINT check_quantita_positive
CHECK (quantita > 0);

-- lista dei prodotti che un fornitore ha a disposizione
CREATE TABLE fornitore_prodotto (
    fornitore VARCHAR(20) NOT NULL,
    prodotto INT NOT NULL,
    costo_unitario NUMERIC(10,2) NOT NULL,
    disponibilita INT NOT NULL CHECK (disponibilita >= 0),
    PRIMARY KEY (fornitore, prodotto),
    FOREIGN KEY (fornitore) REFERENCES fornitore(partita_iva),
    FOREIGN KEY (prodotto) REFERENCES prodotto(id)
);


CREATE TABLE approvvigionamento_prodotto (
    approvvigionamento INT NOT NULL,
    prodotto INT NOT NULL,
    prezzo_unitario NUMERIC(10,2) NOT NULL,
    quantita INT NOT NULL CHECK (quantita > 0),
    PRIMARY KEY (approvvigionamento, prodotto),
    FOREIGN KEY (approvvigionamento) REFERENCES approvvigionamento(id),
    FOREIGN KEY (prodotto) REFERENCES prodotto(id)
);

CREATE OR REPLACE FUNCTION effettua_approvvigionamento(
    prodotto_id INT,
    negozio_id INT,
    data_consegna DATE,
    quantita INT
)
RETURNS VOID AS $$
DECLARE
    fornitore_id VARCHAR(20);
    prezzo_unitario NUMERIC(10,2);
    approvvigionamento_id INT;
BEGIN
    -- cerca il fornitore più economico con abbastanza disponibilità
    SELECT f.fornitore, f.costo_unitario
    INTO fornitore_id, prezzo_unitario
    FROM fornitore_prodotto f
    WHERE f.prodotto = prodotto_id
      AND f.disponibilita >= quantita
    ORDER BY f.costo_unitario ASC
    LIMIT 1;

    -- se non trovato, solleva errore
    IF fornitore_id IS NULL THEN
        RAISE EXCEPTION 'Nessun fornitore ha % pezzi disponibili per il prodotto %', quantita, prodotto_id;
    END IF;

    -- inserisci approvvigionamento
    INSERT INTO approvvigionamento(negozio, fornitore, data_consegna)
    VALUES (negozio_id, fornitore_id, data_consegna)
    RETURNING id INTO approvvigionamento_id;

    -- inserisci dettaglio prodotto
    INSERT INTO approvvigionamento_prodotto(approvvigionamento, prodotto, prezzo_unitario, quantita)
    VALUES (approvvigionamento_id, prodotto_id, prezzo_unitario, quantita);
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION aggiorna_disponibilita_fornitore()
RETURNS TRIGGER AS $$
DECLARE
    fornitore_id VARCHAR(20);
BEGIN
    -- recupera fornitore dalla tabella approvvigionamento
    SELECT fornitore INTO fornitore_id
    FROM approvvigionamento
    WHERE id = NEW.approvvigionamento;

    -- aggiorna disponibilità
    UPDATE fornitore_prodotto
    SET disponibilita = disponibilita - NEW.quantita
    WHERE fornitore = fornitore_id
      AND prodotto = NEW.prodotto;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER after_insert_approvvigionamento_prodotto
AFTER INSERT ON approvvigionamento_prodotto
FOR EACH ROW
EXECUTE FUNCTION aggiorna_disponibilita_fornitore();

CREATE OR REPLACE VIEW storico_ordini_fornitore AS
SELECT
    a.fornitore,
    a.id AS approvvigionamento_id,
    a.data_consegna,
    a.negozio,
    ap.prodotto,
    ap.quantita,
    ap.prezzo_unitario
FROM approvvigionamento a
JOIN approvvigionamento_prodotto ap ON ap.approvvigionamento = a.id
ORDER BY a.fornitore, a.data_consegna DESC;

CREATE OR REPLACE VIEW lista_tesserati_negozio AS
SELECT
    t.negozio,
    t.id AS tessera_id,
    t.data_rilascio,
    u.id AS utente_id,
    u.nome,
    u.cognome,
    u.codice_fiscale
FROM tessera t
JOIN utente u ON t.utente = u.id
ORDER BY t.negozio, t.data_rilascio;

CREATE OR REPLACE VIEW clienti_con_piu_di_300_punti AS
SELECT
    t.id AS tessera_id,
    t.saldo_punti,
    t.data_rilascio,
    t.negozio,
    u.id AS utente_id,
    u.nome,
    u.cognome,
    u.codice_fiscale
FROM tessera t
JOIN utente u ON t.utente = u.id
WHERE t.saldo_punti > 300
ORDER BY t.saldo_punti DESC;

CREATE TABLE prodotto_negozio (
    negozio INT NOT NULL,
    prodotto INT NOT NULL,
    prezzo_vendita NUMERIC(10,2) NOT NULL,
    PRIMARY KEY (negozio, prodotto),
    FOREIGN KEY (negozio) REFERENCES negozio(id),
    FOREIGN KEY (prodotto) REFERENCES prodotto(id)
);

CREATE OR REPLACE FUNCTION check_negozio_non_eliminato_tessera()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM negozio
        WHERE id = NEW.negozio AND eliminato = TRUE
    ) THEN
        RAISE EXCEPTION 'Non è possibile emettere una tessera per un negozio eliminato';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_check_negozio_tessera
BEFORE INSERT ON tessera
FOR EACH ROW
EXECUTE FUNCTION check_negozio_non_eliminato_tessera();

CREATE OR REPLACE FUNCTION check_negozio_non_eliminato_scontrino()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM negozio
        WHERE id = NEW.negozio AND eliminato = TRUE
    ) THEN
        RAISE EXCEPTION 'Non è possibile registrare uno scontrino per un negozio eliminato';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_check_negozio_scontrino
BEFORE INSERT ON scontrino
FOR EACH ROW
EXECUTE FUNCTION check_negozio_non_eliminato_scontrino();

CREATE OR REPLACE FUNCTION check_negozio_non_eliminato_approvv()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM negozio
        WHERE id = NEW.negozio AND eliminato = TRUE
    ) THEN
        RAISE EXCEPTION 'Non è possibile effettuare un approvvigionamento per un negozio eliminato';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_check_negozio_approvv
BEFORE INSERT ON approvvigionamento
FOR EACH ROW
EXECUTE FUNCTION check_negozio_non_eliminato_approvv();


-- utility view
CREATE OR REPLACE VIEW prodotti_offerti_fornitore AS
SELECT
    fp.fornitore AS partita_iva,
    p.nome AS nome_prodotto,
    fp.costo_unitario,
    fp.disponibilita
FROM fornitore_prodotto fp
JOIN prodotto p ON p.id = fp.prodotto
ORDER BY fp.fornitore, p.nome;


CREATE OR REPLACE FUNCTION aggiorna_totale_scontrino()
RETURNS TRIGGER AS $$
DECLARE
    nuovo_totale NUMERIC(10,2);
BEGIN
    SELECT COALESCE(SUM(prezzo_unitario * quantita), 0)
    INTO nuovo_totale
    FROM scontrino_prodotto
    WHERE scontrino = NEW.scontrino;

    UPDATE scontrino
    SET totale_pagato = nuovo_totale
    WHERE id = NEW.scontrino;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_aggiorna_totale_scontrino
AFTER INSERT OR UPDATE OR DELETE ON scontrino_prodotto
FOR EACH ROW
EXECUTE FUNCTION aggiorna_totale_scontrino();

CREATE OR REPLACE FUNCTION applica_sconto_scontrino(scontrino_id INT, usa_punti BOOLEAN)
RETURNS VOID AS $$
DECLARE
    tessera_id INT;
    saldo INT;
    perc_sconto INT := 0;
    punti_usati INT := 0;
    totale NUMERIC(10,2);
    sconto_valore NUMERIC(10,2);
BEGIN
    -- recupera tessera e totale
    SELECT s.tessera, s.totale_pagato INTO tessera_id, totale
    FROM scontrino s
    WHERE s.id = scontrino_id;

    -- se cliente non vuole usare i punti o non ha tessera
    IF NOT usa_punti OR tessera_id IS NULL THEN
        UPDATE scontrino
        SET sconto_percentuale = 0
        WHERE id = scontrino_id;
        RETURN;
    END IF;

    -- recupera saldo punti
    SELECT saldo_punti INTO saldo
    FROM tessera WHERE id = tessera_id;

    -- determina sconto
    IF saldo >= 300 THEN
        perc_sconto := 30;
        punti_usati := 300;
    ELSIF saldo >= 200 THEN
        perc_sconto := 15;
        punti_usati := 200;
    ELSIF saldo >= 100 THEN
        perc_sconto := 5;
        punti_usati := 100;
    ELSE
        perc_sconto := 0;
    END IF;

    -- applica sconto se c'è
    IF perc_sconto > 0 THEN
        sconto_valore := totale * (perc_sconto / 100.0);
        IF sconto_valore > 100 THEN
            sconto_valore := 100;
        END IF;

        UPDATE scontrino
        SET totale_pagato = totale - sconto_valore,
            sconto_percentuale = perc_sconto
        WHERE id = scontrino_id;

        UPDATE tessera
        SET saldo_punti = saldo_punti - punti_usati
        WHERE id = tessera_id;
    ELSE
        UPDATE scontrino
        SET sconto_percentuale = 0
        WHERE id = scontrino_id;
    END IF;
END;
$$ LANGUAGE plpgsql;

