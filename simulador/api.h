// GYM-ZERO · Funcions comunes dels simuladors (màquina i torn)
// Envien un JSON a l'API amb curl i llegeixen la resposta.

#pragma once

#include <cstdio>
#include <cstdlib>
#include <fstream>
#include <string>

using namespace std;

// Envia el JSON amb un POST fent servir curl. Retorna el codi HTTP (0 si no hi ha connexió).
// El JSON es posa en un fitxer temporal; així el que escriu l'usuari mai va dins de l'ordre.
long envia(const string& url, const string& clau, const string& json, string& resposta)
{
    const string fitxer = "/tmp/gymzero_peticio.json";
    ofstream f(fitxer);
    f << json;
    f.close();

    // -w afegeix el codi HTTP al final de la resposta, en una línia a part
    string ordre = "curl -s -m 10 -X POST -H 'Content-Type: application/json'"
                   " -H 'X-Token: " + clau + "' --data-binary @" + fitxer +
                   " -w '\\n%{http_code}' '" + url + "' 2>&1";

    FILE* p = popen(ordre.c_str(), "r");
    if (!p) {
        resposta = "no s'ha pogut executar curl";
        return 0;
    }
    char trosset[512];
    while (fgets(trosset, sizeof trosset, p)) {
        resposta += trosset;
    }
    pclose(p);
    remove(fitxer.c_str());

    size_t salt = resposta.rfind('\n');
    long codi = 0;
    if (salt != string::npos) {
        codi = strtol(resposta.c_str() + salt + 1, nullptr, 10);
        resposta = resposta.substr(0, salt);
    }
    if (codi == 0) {
        resposta = "el servidor no respon (està engegat l'XAMPP? està instal·lat curl?)";
    }
    return codi;
}

// Treu el valor d'un camp de la resposta JSON (prou per a les respostes de la nostra API)
string camp(const string& json, const string& nom)
{
    size_t pos = json.find("\"" + nom + "\":");
    if (pos == string::npos) {
        return "";
    }
    pos += nom.size() + 3;
    if (json[pos] == '"') {
        return json.substr(pos + 1, json.find('"', pos + 1) - pos - 1);
    }
    return json.substr(pos, json.find_first_of(",}", pos) - pos);
}
