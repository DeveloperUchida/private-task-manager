package main

import (
	"context"
	"crypto/rand"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"

	_ "github.com/go-sql-driver/mysql"
	"github.com/gorilla/sessions"
	"golang.org/x/oauth2"
	"golang.org/x/oauth2/google"
)

var (
	oauthConfig  *oauth2.Config
	sessionStore *sessions.CookieStore
	db           *sql.DB
)

type GoogleUser struct {
	ID    string `json:"id"`
	Email string `json:"email"`
	Name  string `json:"name"`
}

func main() {
	oauthConfig = &oauth2.Config{
		ClientID:     os.Getenv("GOOGLE_CLIENT_ID"),
		ClientSecret: os.Getenv("GOOGLE_CLIENT_SECRET"),
		RedirectURL:  os.Getenv("REDIRECT_URL"),
		Scopes: []string{
			"https://www.googleapis.com/auth/userinfo.email",
			"https://www.googleapis.com/auth/userinfo.profile",
		},
		Endpoint: google.Endpoint,
	}

	sessionStore = sessions.NewCookieStore([]byte(os.Getenv("SESSION_SECRET")))
	sessionStore.Options = &sessions.Options{
		Path:     "/",
		MaxAge:   86400 * 7,
		HttpOnly: true,
		Secure:   false,
		SameSite: http.SameSiteLaxMode,
	}

	var err error
	db, err = sql.Open("mysql", os.Getenv("DB_DSN"))
	if err != nil {
		log.Fatal("DB接続失敗:", err)
	}
	defer db.Close()

	http.HandleFunc("/auth/google/login", handleGoogleLogin)
	http.HandleFunc("/auth/callback", handleGoogleCallback)
	http.HandleFunc("/auth/logout", handleLogout)
	http.HandleFunc("/auth/me", handleMe)

	log.Println("Go Auth Server起動 :8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}

func handleGoogleLogin(w http.ResponseWriter, r *http.Request) {
	state := generateState()
	session, _ := sessionStore.Get(r, "oauth-state")
	session.Values["state"] = state
	session.Save(r, w)
	url := oauthConfig.AuthCodeURL(state, oauth2.AccessTypeOffline)
	http.Redirect(w, r, url, http.StatusTemporaryRedirect)
}

func handleGoogleCallback(w http.ResponseWriter, r *http.Request) {
	session, _ := sessionStore.Get(r, "oauth-state")
	if r.URL.Query().Get("state") != session.Values["state"] {
		http.Error(w, "state不一致", http.StatusBadRequest)
		return
	}
	code := r.URL.Query().Get("code")
	token, err := oauthConfig.Exchange(context.Background(), code)
	if err != nil {
		http.Error(w, "トークン取得失敗", http.StatusInternalServerError)
		return
	}
	googleUser, err := getGoogleUserInfo(token)
	if err != nil {
		http.Error(w, "ユーザー情報取得失敗", http.StatusInternalServerError)
		return
	}
	userID, err := upsertGoogleUser(googleUser)
	if err != nil {
		http.Error(w, "DBエラー", http.StatusInternalServerError)
		return
	}
	userSession, _ := sessionStore.Get(r, "user-session")
	userSession.Values["user_id"] = userID
	userSession.Values["email"] = googleUser.Email
	userSession.Values["auth_type"] = "google"
	userSession.Save(r, w)
	http.Redirect(w, r, "/app/tasks.php", http.StatusSeeOther)
}

func handleLogout(w http.ResponseWriter, r *http.Request) {
	session, _ := sessionStore.Get(r, "user-session")
	session.Options.MaxAge = -1
	session.Save(r, w)
	http.Redirect(w, r, "/", http.StatusSeeOther)
}

func handleMe(w http.ResponseWriter, r *http.Request) {
	session, _ := sessionStore.Get(r, "user-session")
	userID, ok := session.Values["user_id"]
	if !ok {
		http.Error(w, "未認証", http.StatusUnauthorized)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"user_id":   userID,
		"email":     session.Values["email"],
		"auth_type": session.Values["auth_type"],
	})
}

func getGoogleUserInfo(token *oauth2.Token) (*GoogleUser, error) {
	client := oauthConfig.Client(context.Background(), token)
	resp, err := client.Get("https://www.googleapis.com/oauth2/v2/userinfo")
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	var user GoogleUser
	if err := json.NewDecoder(resp.Body).Decode(&user); err != nil {
		return nil, err
	}
	return &user, nil
}

func upsertGoogleUser(u *GoogleUser) (int64, error) {
	result, err := db.Exec(`
		INSERT INTO users (google_id, email)
		VALUES (?, ?)
		ON DUPLICATE KEY UPDATE email = VALUES(email)
	`, u.ID, u.Email)
	if err != nil {
		return 0, err
	}
	id, err := result.LastInsertId()
	if err != nil || id == 0 {
		err = db.QueryRow("SELECT id FROM users WHERE google_id = ?", u.ID).Scan(&id)
	}
	return id, err
}

func generateState() string {
	b := make([]byte, 16)
	if _, err := rand.Read(b); err != nil {
		return "fallback-state"
	}
	return fmt.Sprintf("%x", b)
}