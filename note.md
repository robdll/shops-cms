# tessere
è stato inserito un ulteriore trigger per salvare le informazioni di tessere eliminate dalla tabella così da poter assistere in caso di errori 

# storico tessere
se un negozio viene eliminato vengono salvati su storico_tessere i record delle tessere emesse, tuttavia, se il negozio dovessere essere riattivato, e in futuro eliminato nuovamente, si otterrebbero record duplicati.
E' possibile avere comportamenti alternativi in base all'utilità della tabella di storico_tessere, che richiederebbero un approfondimento con il committente. Alcuni comportamenti attivi applicabili sono:
- in caso di restore di un negozio si potrebbe eliminare dallo storico i record associati a tale negozio.
- in caso di eliminazione di un negozio si potrebbe evitare di inserire record già presenti nella tabella storico tessere
- in caso di impossibilità di restore di un negozio si potrebbe evitare la possibilità che tale aggiornamento venga effettuato.


